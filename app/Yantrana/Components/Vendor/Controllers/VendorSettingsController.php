<?php

/**
 * VendorSettingsController.php - Controller file
 *
 * This file is part of the Vendor component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Vendor\Controllers;

use Illuminate\Http\Request; // Asegúrate de importar Request
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseRequest;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\Vendor\VendorEngine;
use App\Yantrana\Components\BotReply\BotReplyEngine;
use App\Yantrana\Components\Vendor\VendorSettingsEngine;
use App\Yantrana\Components\Vendor\Requests\VendorSettingsRequest;

//agregados
use App\Yantrana\Components\Vendor\Models\VendorSettingsModel;
use App\Yantrana\Components\Vendor\Models\VendorModel;
use App\Yantrana\Components\Auth\Models\AuthModel;

class VendorSettingsController extends BaseController
{
    /**
     * @var VendorSettingsEngine - VendorSettings Engine
     */
    protected $vendorSettingsEngine;

    /**
     * @var VendorEngine - VendorSettings Engine
     */
    protected $vendorEngine;

    /**
     * @var  BotReplyEngine $botReplyEngine - BotReply Engine
     */
    protected $botReplyEngine;

    /**
     * Constructor
     *
     * @param  VendorSettingsEngine  $vendorSettingsEngine  - VendorSettings Engine
     * @param  BotReplyEngine $botReplyEngine - BotReply Engine

     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(
        VendorSettingsEngine $vendorSettingsEngine,
        VendorEngine $vendorEngine,
        BotReplyEngine $botReplyEngine)
    {
        $this->vendorSettingsEngine = $vendorSettingsEngine;
        $this->vendorEngine = $vendorEngine;
        $this->botReplyEngine = $botReplyEngine;
    }

    /**
     * Vendor Settings View
     *
     * @return view
     *---------------------------------------------------------------- */
    public function index($pageType = 'general')
    {
        validateVendorAccess('administrative');
        $basicSettings = $this->vendorEngine->getBasicSettings();
        $processReaction = $this->vendorSettingsEngine->prepareConfigurations(Str::of($pageType)->slug('_'));
        $otherData = [];
        if($pageType == 'api-access') {
            // dynamicFields
            $otherData['dynamicFields'] = $this->botReplyEngine->preDataForBots()->data('dynamicFields');
        }
        // check if settings available
        abortIf(!file_exists(resource_path("views/vendors/settings/$pageType.blade.php")));
        // load view
        return $this->loadView('vendors.settings.index', array_merge([
            'pageType' => $pageType,
            'basicSettings' => $basicSettings,
        ], $processReaction['data'], $otherData), [
            'compress_page' => false
        ]);
    }

    /**
     * Get Configuration Data.
     *
     * @param  BaseRequest  $request
     * @return json object
     *---------------------------------------------------------------- */
    public function update(VendorSettingsRequest $request)
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $validationRules = [
            'pageType' => 'required',
        ];
        $request->validate($this->settingsValidationRules($request->pageType, $validationRules,$request->all()));
        $processReaction = $this->vendorSettingsEngine->updateProcess($request->pageType, $request->all());

        return $this->responseAction($this->processResponse($processReaction, [], [], true));
    }

    /**
     * Get Configuration Data.
     *
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function updateBasicSettings(BaseRequest $request)
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $validationRules = [
            'store_name' => [
                'required',
                'max:200',
            ],
        ];
        $request->validate($this->settingsValidationRules($request->pageType, $validationRules,$request->all()));
        $processReaction = $this->vendorSettingsEngine->updateBasicSettingsProcess($request->all());

        return $this->responseAction($this->processResponse($processReaction, [], [], true));
    }

    /**
     * Setup validation array
     *
     * @param  string  $pageType
     * @param  array  $validationRules
     * @param  array  $inputFields
     * @return mixed
     */
    protected function settingsValidationRules($pageType, $validationRules = [], $inputFields = [])
    {
        if (! $pageType) {
            return $validationRules;
        }
        foreach (config('__vendor-settings.items.' . $pageType) as $settingItemKey => $settingItemValue) {
            $settingsValidationRules = Arr::get($settingItemValue, 'validation_rules', []);
            $isValueHidden = Arr::get($settingItemValue, 'hide_value');
            if ($settingsValidationRules) {
                // skip validation if hidden value item and empty and the value is already set
                if(!array_key_exists($settingItemKey, $inputFields) or ($isValueHidden and empty(Arr::get($inputFields, $settingItemKey)) and getVendorSettings($settingItemKey))) {
                    continue;
                }
                $existingItemRules = Arr::get($validationRules, $settingItemKey, []);
                $validationRules[$settingItemKey] = array_merge(
                    ! is_array($existingItemRules) ? [$existingItemRules] : $existingItemRules,
                    $settingsValidationRules
                );
            }
        }
        return $validationRules;
    }



    public function disableSoundForMessageNotification() {
        // as it cones from flash memory so it won't be fresh data in single request
        // thats why we have applied reverse logic here
        $isSoundDisabled = getVendorSettings('is_disabled_message_sound_notification');
        $this->vendorSettingsEngine->updateProcess('internals', [
            'is_disabled_message_sound_notification' => $isSoundDisabled ? false : true
        ]);
        updateClientModels([
            'disableSoundForMessageNotification' =>  !$isSoundDisabled
        ]);
        return $this->processResponse(1, [
            1 => $isSoundDisabled ? __tr('Sound for message notification enabled') : __tr('Sound for message notification disabled')
        ], [], true);
    }
	
	/**
	 * Obtiene de forma dinámica los settings solicitados para un vendor.
	 *
	 * Ejemplo de URL:
	 * GET /{vendorUid}/vendor-settings?keys=flowise_ai_url,flowise_access_token
	 *
	 * @param  string  $vendorUid
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSettings($vendorUid, Request $request)
	{
		$automate_id = $request->query('automate_id');
		$userUid = $request->query('user_uid');
		$keys = "flowise_url";
		
		$status_automate = false;
		$is_admin = false;

		// Permitir tanto un array como una cadena separada por comas
		if (!is_array($keys)) {
			$keys = explode(',', $keys);
		}
		
		//Auth
		
		
		$userAuthData = AuthModel::where('_uid', $userUid)
								->first();
		
		if(isset($userAuthData)){
			
			if($userAuthData['user_roles__id'] == 1 && $userAuthData['vendors__id'] == null){
				
				$status_automate = true;
				$is_admin = true;
				
			}else{
				
				
				// Buscar el vendor usando el _uid proporcionado, a través del modelo VendorModel
				$vendor = VendorModel::where('_uid', $vendorUid)->first();

				if (!$vendor) {
					return response()->json([
						'error' => 'Vendor no encontrado.'
					], 404);
				}

				// Obtener el _id del vendor
				$vendorId = $vendor->_id;
				
				
				//verificar si el usuario esta vinculado al vendor
				$userVendor = AuthModel::where('_uid', $userUid)
								->where('vendors__id', $vendorId)
								->first();
				
				if (!$userVendor) {
					return response()->json([
						'error' => 'Usuario no Vinculado al Vendor.'
					], 404);
				}

				// Buscar el setting "flowise_url" para el vendor utilizando el modelo VendorSettingsModel
				$flowiseSetting = VendorSettingsModel::where('vendors__id', $vendorId)
									->where('name', 'flowise_url')
									->first();

				// Suponiendo que el valor de la URL se almacena en la propiedad "value" del modelo
				$flowise_url = $flowiseSetting ? $flowiseSetting->value : null;
				$flowise_id = "";

				if ($flowise_url) {
					// Extraer el ID de la URL.
					// Ejemplo: de "https://automate.alfabusiness.app/api/v1/prediction/bdc8d9fb-dd79-47bb-991e-14ae049172e0"
					// se extrae "bdc8d9fb-dd79-47bb-991e-14ae049172e0".
					$parsedPath = parse_url($flowise_url, PHP_URL_PATH); // "/api/v1/prediction/bdc8d9fb-dd79-47bb-991e-14ae049172e0"
					$segments = explode('/', trim($parsedPath, '/')); // ["api", "v1", "prediction", "bdc8d9fb-dd79-47bb-991e-14ae049172e0"]
					if (!empty($segments)) {
						$flowise_id = end($segments);
					}
				}



				if($automate_id == $flowise_id){
					$status_automate = true;
				}
			}
			
		}
		
		return response()->json([
			//'vendorUid'   => $vendorUid,
			//'automate_id' => $automate_id,
			//'flowise_id'  => $flowise_id,
			'login_automate'  => $status_automate,
			'is_admin' => $is_admin,
		]);
	}
	
}
