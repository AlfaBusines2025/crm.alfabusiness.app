<?php
/**
* ContactController.php - Controller file
*
* This file is part of the Contact component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Controllers;

use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseRequest;
use Illuminate\Support\Facades\Gate;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequestTwo;
use Illuminate\Database\Query\Builder;
use App\Yantrana\Components\Contact\ContactEngine;

//agregados
use Illuminate\Http\Request;
//use Barryvdh\DomPDF\Facade\Pdf;
//use Pdf;
use Dompdf\Dompdf;


class ContactController extends BaseController
{
    /**
     * @var ContactEngine - Contact Engine
     */
    protected $contactEngine;

    /**
     * Constructor
     *
     * @param  ContactEngine  $contactEngine  - Contact Engine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(ContactEngine $contactEngine)
    {
        $this->contactEngine = $contactEngine;
    }

    /**
     * list of Contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function showContactView($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        $contactsRequiredEngineResponse = $this->contactEngine->prepareContactRequiredData($groupUid);

        // load the view
        return $this->loadView('contact.list', $contactsRequiredEngineResponse->data());
    }

    /**
     * list of Contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function prepareContactList($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        // respond with dataTables preparations
        return $this->contactEngine->prepareContactDataTableSource($groupUid);
    }

    /**
     * Contact process delete
     *
     * @param  mix  $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processContactDelete($contactIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactDelete($contactIdOrUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Contact process remove from group
     *
     * @param  mix  $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processContactRemoveFromGroup($contactIdOrUid, $groupUid, BaseRequest $request)
    {

        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactRemove($contactIdOrUid, $groupUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Selected Contacts delete process
     *
     * @param  BaseRequest  $request
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function selectedContactsDelete(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');

        // restrict demo user
        if (isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $request->validate([
            'selected_contacts' => 'required|array'
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processSelectedContactsDelete($request);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Selected Contacts delete process
     *
     * @param  BaseRequest  $request
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function assignGroupsToSelectedContacts(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        $request->validate([
            'selected_contacts' => 'required|array',
            'selected_groups' => 'required|array'
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processAssignGroupsToSelectedContacts($request);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Contact create process
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function processContactCreate(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        $request->validate([
            'language_code' => 'nullable|alpha_dash',
            "phone_number" => [
                'required',
                'numeric',
                'min_digits:9',
                'min:1',
                'doesnt_start_with:+,0',
                Rule::unique('contacts', 'wa_id')->where(fn (Builder $query) => $query->where('vendors__id', getVendorId()))
            ],
            'email' => 'nullable|email',
        ]);

        if (str_starts_with($request->get('phone_number'), '0') or str_starts_with($request->get('phone_number'), '+')) {
            return $this->processResponse(2, __tr('Mobile number should be numeric value without prefixing 0 or +'));
        }

        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactCreate($request->all());

        // get back with response
        return $this->processResponse($processReaction);
    }
	
    /**
	 * Contact create process by API
	 *
	 * @param  object BaseRequest $request
	 * @return json object
	 *---------------------------------------------------------------- */
	
	public function apiProcessContactCreate(BaseRequest $request)
	{
		validateVendorAccess('manage_contacts');

		// Validaciones base
		$request->validate([
			'language_code' => 'nullable|alpha_dash',
			"phone_number" => [
				'required',
				'numeric',
				'min_digits:9',
				'min:1',
				'doesnt_start_with:+,0',
				Rule::unique('contacts', 'wa_id')
					->where(fn (Builder $query) => $query->where('vendors__id', getVendorId()))
			],
			'email' => 'nullable|email',
		]);

		// Abortar si empieza con 0 o +
		abortIf(
			str_starts_with($request->get('phone_number'), '0') 
			or str_starts_with($request->get('phone_number'), '+'), 
			null, 
			'phone number should be numeric value without prefixing 0 or +'
		);

		// 1. Tomar todos los datos originales
		$inputData = $request->all();

		// 2. Convertir "country" a su ID interno si aplica
		$inputData['country'] = getCountryIdByName($inputData['country'] ?? null);

		// 3. Asegurar "labels" y "custom_input_fields"
		$inputData['labels']              = $request->get('labels', '');
		$inputData['custom_input_fields'] = $request->get('custom_input_fields', []);

		// --------------------------------------
		// Bloque de depuración
		// --------------------------------------
		//  (A) Log interno en laravel.log
		\Log::debug('apiProcessContactCreate - Received inputData', [
			'inputData'        => $inputData,
			'isExternalApiReq' => isExternalApiRequest(),
		]);

		try {
			// 4. Llamar a processContactCreate en el engine
			$processReaction = $this->contactEngine->processContactCreate($inputData);
			$contact = $processReaction->data('contact');

			// (B) Depuración en JSON: podemos crear un objeto "debug" con info adicional
			// para verlo en Postman / tu frontend
			$debugInfo = [
				'inputData_after_prepare' => $inputData,
				'engine_reaction'         => [
					'success' => $processReaction->success(),
					'message' => $processReaction->message(),
					'data'    => $processReaction->data(),
				],
				'isExternalApiReq' => isExternalApiRequest(),
			];

			// 5. Retornar tu respuesta "oficial" + sección "debug"
			return response()->json([
				'status'   => $processReaction->success() ? 'success' : 'error',
				'message'  => $processReaction->message() ?? 'No message',
				'data'     => [
					'contact_uid'    => $contact?->_uid,
					'first_name'     => $contact?->first_name,
					'last_name'      => $contact?->last_name,
					'phone_number'   => $contact?->wa_id,
					'language_code'  => $contact?->language_code,
					'country'        => $contact?->country?->name,
				],
				'debug'    => $debugInfo  // <-- objeto con data de depuración
			], 200);

		} catch (\Exception $e) {
			// Si ocurre un error inesperado, devolvemos JSON con el mensaje y stack trace
			\Log::error('apiProcessContactCreate - Exception', [
				'message' => $e->getMessage(),
				'trace'   => $e->getTraceAsString(),
			]);

			return response()->json([
				'status' => 'error',
				'error'  => $e->getMessage(),
				'trace'  => $e->getTraceAsString(),  // opcional: para ver más detalles
			], 500);
		}
	}

    /**
	 * API Contact process update
	 *
	 * @param  object BaseRequest $request
	 * @param  string $vendorUid
	 * @param  string $phoneNumber
	 * @return json object
	 *---------------------------------------------------------------- */
	public function apiProcessContactUpdate(BaseRequest $request, $vendorUid, $phoneNumber)
	{
		validateVendorAccess('manage_contacts');

		// Validaciones base
		$request->validate([
			'first_name'           => 'nullable|string|max:255',
			'last_name'            => 'nullable|string|max:255',
			'language_code'        => 'nullable|alpha_dash',
			'email'                => 'nullable|email',
			'country'              => 'nullable|string|max:255', // Asegúrate de que el país se maneje correctamente
			'groups'               => 'nullable|string', // Grupos separados por comas
			'labels'               => 'nullable|string', // Etiquetas separadas por comas
			'custom_input_fields'  => 'nullable|array',
			'whatsapp_opt_out'     => 'nullable|boolean',
			'enable_ai_bot'        => 'nullable|boolean',
		]);

		// Abortar si empieza con 0 o +
		abortIf(
			str_starts_with($request->get('phone_number'), '0') 
			|| str_starts_with($request->get('phone_number'), '+'), 
			null, 
			'Phone number should be numeric value without prefixing 0 or +'
		);

		// Tomar todos los datos originales
		$inputData = $request->all();

		// Convertir "country" a su ID interno si aplica
		if (isset($inputData['country'])) {
			$inputData['country'] = getCountryIdByName($inputData['country']);
		}

		// Asegurar "labels" y "custom_input_fields"
		$inputData['labels']              = $request->get('labels', '');
		$inputData['custom_input_fields'] = $request->get('custom_input_fields', []);

		// Agregar datos de debug
		\Log::debug('apiProcessContactUpdate - Received inputData', [
			'inputData'        => $inputData,
			'isExternalApiReq' => isExternalApiRequest(),
		]);

		try {
			// Llamar a processContactUpdate en el engine
			$processReaction = $this->contactEngine->processContactUpdate($phoneNumber, $inputData);
			$contact = $processReaction->data('contact');

			// Depuración en JSON
			$debugInfo = [
				'inputData_after_prepare' => $inputData,
				'engine_reaction'         => [
					'success' => $processReaction->success(),
					'message' => $processReaction->message(),
					'data'    => $processReaction->data(),
				],
				'isExternalApiReq' => isExternalApiRequest(),
			];

			// Retornar la respuesta oficial + sección de depuración
			return response()->json([
				'status'   => $processReaction->success() ? 'success' : 'error',
				'message'  => $processReaction->message() ?? 'No message',
				'data'     => [
					'contact_uid'    => $contact?->_uid,
					'first_name'     => $contact?->first_name,
					'last_name'      => $contact?->last_name,
					'phone_number'   => $contact?->wa_id,
					'language_code'  => $contact?->language_code,
					'country'        => $contact?->country?->name,
					'email'          => $contact?->email,
					'groups'         => $contact?->groups->pluck('title')->toArray(),
					'labels'         => $contact?->labels->pluck('title')->toArray(),
					'custom_fields'  => $contact?->customFieldValues->mapWithKeys(function($item) {
						return [$item->customField->input_name => $item->field_value];
					}),
				],
				'debug'    => $debugInfo  // <-- objeto con data de depuración
			], 200);

		} catch (\Exception $e) {
			// Registrar el error en los logs
			\Log::error('apiProcessContactUpdate - Exception', [
				'message' => $e->getMessage(),
				'trace'   => $e->getTraceAsString(),
			]);

			// Devolver una respuesta de error
			return response()->json([
				'status' => 'error',
				'error'  => $e->getMessage(),
				'trace'  => $e->getTraceAsString(),  // opcional: para ver más detalles
			], 500);
		}
	}

    /**
     * Contact get update data
     *
     * @param  mix  $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function updateContactData($contactIdOrUid)
    {
        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->contactEngine->prepareContactUpdateData($contactIdOrUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Contact process update
     *
     * @param  mix @param  mix $contactIdOrUid
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function processContactUpdate(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        $request->validate([
            'contactIdOrUid' => 'required',
            'email' => 'nullable|email',
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactUpdate($request->get('contactIdOrUid'), $request->all());

        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }
	
	/**
	 * Mostrar la información completa de un contacto vía GET,
	 * recibiendo el _uid del contacto y el wa_id (teléfono).
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  string $vendorUid
	 * @param  string $contactUid
	 * @param  string $phoneNumber
	 * @return \Illuminate\Http\JsonResponse
	 */
    public function apiShowContact(Request $request, $vendorUid, $contactUid)
	{
		try {
			// 2. Consultar el contacto usando el método del engine
			$contact = $this->contactEngine->getContactByUidAndWaId(
				$contactUid,      // _uid del contacto
				getVendorId()     // ID del vendor
			);

			// 3. Si no se encontró, retornamos 404
			if (!$contact) {
				return response()->json([
					'status'  => 'error',
					'message' => 'Contacto no encontrado.'
				], 404);
			}

			// 4. Construir la respuesta con la info que necesitas
			$responseData = [
				'contact_uid'       => $contact->_uid,
				'first_name'        => $contact->first_name,
				'last_name'         => $contact->last_name,
				'phone_number'      => $contact->wa_id, 
				'language_code'     => $contact->language_code,
				'country'           => optional($contact->country)->name,
				'email'             => $contact->email,
				'whatsapp_opt_out'  => $contact->whatsapp_opt_out,
				'enable_ai_bot'     => !($contact->disable_ai_bot),
				'groups'            => $contact->groups->pluck('title')->toArray(),
				'labels'            => $contact->labels->pluck('title')->toArray(),
				'custom_fields'     => $contact->customFieldValues->mapWithKeys(function ($item) {
					return [
						$item->customField->input_name => $item->field_value
					];
				}),
			];

			return response()->json([
				'status'  => 'success',
				'message' => 'Contacto obtenido con éxito.',
				'data'    => $responseData,
			], 200);

		} catch (\Exception $e) {
			// 5. Manejo de excepciones
			\Log::error('apiShowContact - Exception', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			]);

			return response()->json([
				'status'  => 'error',
				'message' => 'Error al obtener el contacto.',
				'error'   => $e->getMessage(),
			], 500);
		}
	}
	
    /**
     * Toggle AI Bot for COntact
     *
     * @param  int|string $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function toggleAiBot(BaseRequest $request, $contactIdOrUid)
    {
        validateVendorAccess('messaging');
        // ask engine to process the request
        $processReaction = $this->contactEngine->processToggleAiBot($contactIdOrUid);
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Export Contacts
     *
     * @param string $exportType
     * @return file
     */
    public function exportContacts($exportType = null)
    {

        validateVendorAccess('manage_contacts');
        return $this->contactEngine->processExportContacts($exportType);
    }

    /**
     * Import Contacts
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function importContacts(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_contacts');
        // restrict demo user
        if (isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $request->validate([
            'document_name' => 'required'
        ]);
        return $this->processResponse(
            $this->contactEngine->processImportContacts($request),
            [],
            [],
            true
        );
    }

    /**
     * Contact process update
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function assignChatUser(BaseRequest $request)
    {
        validateVendorAccess('messaging');
        // process the validation based on the provided rules
        $request->validate([
            'contactIdOrUid' => 'required|uuid',
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processAssignChatUser($request);
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Contact notes process update
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function updateNotes(BaseRequest $request)
    {
        validateVendorAccess('messaging');
        // process the validation based on the provided rules
        $request->validate([
            'contactIdOrUid' => 'required|uuid',
            // 'contact_notes' => 'nullable',
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processUpdateNotes($request);
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Get all the labels
     *
     * @param [type] $contactUid
     * @return void
     */
    public function getLabels($contactUid)
    {
        validateVendorAccess('messaging');
        $processReaction = $this->contactEngine->getLabelsData($contactUid);
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Create new label for vendor
     *
     * @param BaseRequestTwo $request
     * @return void
     */
    public function createLabel(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        $request->validate([
            'title' => [
                'required',
                'max:45',
                Rule::unique('labels')->where(fn (Builder $query) => $query->where('vendors__id', getVendorId()))
            ],
            'text_color' => [
                'nullable',
                'string',
                'max:10',
            ],
            'bg_color' => [
                'nullable',
                'string',
                'max:10',
            ],
        ]);
        $processReaction = $this->contactEngine->createLabelProcess($request);
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Update label for vendor
     *
     * @param BaseRequestTwo $request
     * @return void
     */
    public function updateLabel(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        $request->validate([
            'labelUid' => [
                'required',
                'uuid'
            ],
            'title' => [
                'required',
                'max:45',
                Rule::unique('labels')->where(fn (Builder $query) => $query->where('vendors__id', getVendorId()))->ignore($request->labelUid, '_uid')
            ],
            'text_color' => [
                'nullable',
                'string',
                'max:10',
            ],
            'bg_color' => [
                'nullable',
                'string',
                'max:10',
            ],
        ]);
        $processReaction = $this->contactEngine->processUpdateLabel($request);
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Assign labels to contact
     *
     * @param BaseRequestTwo $request
     * @return void
     */
    public function assignContactLabels(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        $request->validate([
            'contactUid' => [
                'required',
                'uuid',
            ],
            'contact_labels' => [
                'nullable',
                'array',
                // 'max:10',
            ],
        ]);
        $processReaction = $this->contactEngine->assignContactLabelsProcess($request);
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Delete label
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function deleteLabelProcess(BaseRequestTwo $request, $labelUid)
    {
        validateVendorAccess('messaging');
        $request->merge([
            'labelUid' => $request->labelUid
        ]);
        $request->validate([
            'labelUid' => [
                'required',
                'uuid',
            ],
        ]);
        $processReaction = $this->contactEngine->processDeleteLabel($labelUid);
        return $this->processResponse($processReaction, [], [], true);
    }
	
	//funciones personalizadas
	
	/**
	 * Descargar información de un contacto en PDF.
	 *
	 * @param  Request  $request
	 * @param  string   $contactUid   Identificador único del contacto
	 * @return \Illuminate\Http\Response
	 */
	public function descargarContactoPDF(Request $request, $contactUid)
	{
		// Validar acceso al vendor o al módulo de contactos
		validateVendorAccess('manage_contacts');

		// Recuperar la información del contacto mediante el engine
		$contact = $this->contactEngine->getContactByUidAndWaId($contactUid, getVendorId());

		if (!$contact) {
			abort(404, 'Contacto no encontrado.');
		}

		// Crear el HTML inline con la información del contacto
		$html = '<html>
			<head>
				<meta charset="utf-8">
				<title>Información del Contacto</title>
				<style>
					body { font-family: Arial, sans-serif; }
					h1 { color: #333; }
					p { font-size: 14px; }
				</style>
			</head>
			<body>
				<h1>Información del Contacto</h1>
				<p><strong>Fecha:</strong> ' . date('d-m-Y') . '</p>
				<p><strong>Nombre:</strong> ' . $contact->first_name . ' ' . $contact->last_name . '</p>
				<p><strong>Teléfono:</strong> ' . $contact->wa_id . '</p>
				<p><strong>Email:</strong> ' . ($contact->email ?? 'No definido') . '</p>
			</body>
		</html>';

		// Instanciar Dompdf y cargar el HTML
		$dompdf = new Dompdf();
		$dompdf->loadHtml($html);

		// (Opcional) Configurar el tamaño y la orientación del papel
		$dompdf->setPaper('A4', 'portrait');

		// Renderizar el HTML como PDF
		$dompdf->render();

		// Retornar la respuesta para descarga, con las cabeceras adecuadas
		return response($dompdf->output(), 200)
				->header('Content-Type', 'application/pdf')
				->header('Content-Disposition', 'attachment; filename="contacto_' . $contactUid . '.pdf"');
	}
	
}
