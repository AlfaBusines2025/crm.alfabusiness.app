@php
$hasActiveLicense = true;
if(isLoggedIn() and (request()->route()->getName() != 'manage.configuration.product_registration') and (!getAppSettings('product_registration', 'registration_id') or sha1(array_get($_SERVER, 'HTTP_HOST', '') . getAppSettings('product_registration', 'registration_id') . '4.5+') !== getAppSettings('product_registration', 'signature'))) {
    $hasActiveLicense = false;
    if(hasCentralAccess()) {
        header("Location: " . route('manage.configuration.product_registration'));
        exit;
    }
}

// Colocar la cookie vendor_uid cada vez que se loguea
		// Se establece por 12000 segundos (200 minutos) y es válida para todo el dominio .alfabusiness.app
		$vendorUidCookie = getVendorUid();
		if (empty($vendorUidCookie)) {
			$vendorUidCookie = "autocookie";
		}

		// Establecer la cookie (opcional, si necesitas almacenarlo en el navegador)
		setcookie("vendorUid", $vendorUidCookie, time() + 12000000, '/', '.alfabusiness.app'); // Expira en 30 días
		setcookie('userUid', getUserUid(), time() + 12000000, '/', '.alfabusiness.app');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $CURRENT_LOCALE_DIRECTION }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> {{ (isset($title) and $title) ? $title : __tr('Welcome') }} - {{ getAppSettings('name') }}</title>
    <!-- Favicon -->
    <link href="{{getAppSettings('favicon_image_url') }}" rel="icon">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    @stack('head')
    {!! __yesset(
    [
    // Icons
    'static-assets/packages/fontawesome/css/all.css',
    'dist/css/common-vendorlibs.css',
    'dist/css/vendorlibs.css',
    'argon/css/argon.min.css',
    'dist/css/app.css',
    ]) !!}
    {{-- custom app css --}}
    <link href="{{ route('app.load_custom_style') }}" rel="stylesheet" />
    <!-- Pusher Beams SDK -->
    <script src="https://js.pusher.com/beams/1.0/push-notifications-cdn.js"></script>
</head>
<body class="@if(hasVendorAccess() or hasVendorUserAccess()) lw-minimized-menu @endif pb-5 @if(isLoggedIn()) lw-authenticated-page @else lw-guest-page @endif {{ $class ?? '' }}" x-cloak x-data="{disableSoundForMessageNotification:{{ getVendorSettings('is_disabled_message_sound_notification') ? 1 : 0 }},unreadMessagesCount:null}">
    @auth()
    @include('layouts.navbars.sidebar')
    @endauth

    <div class="main-content">
        @include('layouts.navbars.navbar')
        @if(isDemo())
        <div class="container">
            <div class="row">
                <a class="alert alert-danger col-12 mt-md-8 mt-sm-4 mb-sm--3 text-center text-white" target="_blank" href="https://codecanyon.net/item/whatsjet-saas-a-whatsapp-marketing-platform-with-bulk-sending-campaigns-chat-bots/51167362">
                    {{  __tr('Please Note: We sell this script only through CodeCanyon.net at ') }} https://codecanyon.net/item/whatsjet-saas-a-whatsapp-marketing-platform-with-bulk-sending-campaigns-chat-bots/51167362
                </a>
            </div>
        </div>
        @endif
            @if ($hasActiveLicense)
            @if(hasVendorAccess())
            <div class="container">
                <div class="row">
                    <div class="col-12 mt-5 mb--7 pt-5 text-center">
                        @php
                        $vendorPlanDetails = vendorPlanDetails(null, null, getVendorId());
                        @endphp
                        @if(!$vendorPlanDetails->hasActivePlan())
                            <div class="alert alert-danger">
                                {{  $vendorPlanDetails->message }}
                            </div>
                        @elseif($vendorPlanDetails->is_expiring)
                            <div class="alert alert-warning">
                                {{  __tr('Your subscription plan is expiring on __endAt__', [
                                    '__endAt__' => formatDate($vendorPlanDetails->ends_at)
                                ]) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
            @yield('content')
            @else
            <div class="container">
                <div class="row">
                    <div class="col-12 my-5 py-5 text-center">
                       <div class="card my-5 p-5">
                        <i class="fas fa-exclamation-triangle fa-6x mb-4 text-warning"></i>
                        <div class="alert alert-danger my-5">
                            {{ __tr('Product has not been verified yet, please contact via profile or product page.') }}
                        </div>
                       </div>
                    </div>
                </div>
            </div>
            @endif
    </div>
    @guest()
    @include('layouts.footers.guest')
    @endguest
    <?= __yesset(['dist/js/common-vendorlibs.js','dist/js/vendorlibs.js', 'argon/bootstrap/dist/js/bootstrap.bundle.min.js', 'argon/js/argon.js'], true) ?>
    @stack('js')
    @if (hasVendorAccess() or hasVendorUserAccess())
    {{-- QR CODE model --}}
    <x-lw.modal id="lwScanMeDialog" :header="__tr('Scan QR Code to Start Chat')">
        @if (getVendorSettings('current_phone_number_number'))
        <div class="alert alert-dark text-center text-success">
            {{  __tr('You can use following QR Codes to invite people to get connect with you on this platform.') }}
        </div>
        @if (!empty(getVendorSettings('whatsapp_phone_numbers')))
        @foreach (getVendorSettings('whatsapp_phone_numbers') as $whatsappPhoneNumber)
        <fieldset class="text-center">
            <legend class="text-center">{{ $whatsappPhoneNumber['verified_name'] }} ({{ $whatsappPhoneNumber['display_phone_number'] }})</legend>
        <div class="text-center">
            <img class="lw-qr-image" src="{{ route('vendor.whatsapp_qr', [
            'vendorUid' => getVendorUid(),
            'phoneNumber' => cleanDisplayPhoneNumber($whatsappPhoneNumber['display_phone_number']),
        ]) }}">
        </div>
        <div class="form-group">
            <h3 class="text-muted">{{  __tr('Phone Number') }}</h3>
            <h3 class="text-success">{{ $whatsappPhoneNumber['display_phone_number'] }}</h3>
            <label for="lwWhatsAppQRImage{{ $loop->index }}">{{ __tr('URL for QR Image') }}</label>
            <div class="input-group">
                <input type="text" class="form-control" readonly id="lwWhatsAppQRImage{{ $loop->index }}" value="{{ route('vendor.whatsapp_qr', [
                    'vendorUid' => getVendorUid(),
                    'phoneNumber' => cleanDisplayPhoneNumber($whatsappPhoneNumber['display_phone_number']),
                ]) }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-light" type="button"
                        onclick="lwCopyToClipboard('lwWhatsAppQRImage{{ $loop->index }}')">
                        <?= __tr('Copy') ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="form-group">
            <h3 class="text-muted">{{  __tr('WhatsApp URL') }}</h3>
            <div class="input-group">
                <input type="text" class="form-control" readonly id="lwWhatsAppUrl{{ $loop->index }}" value="https://wa.me/{{ cleanDisplayPhoneNumber($whatsappPhoneNumber['display_phone_number']) }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-light" type="button"
                        onclick="lwCopyToClipboard('lwWhatsAppUrl{{ $loop->index }}')">
                        <?= __tr('Copy') ?>
                    </button>
                    <a type="button" class="btn btn-outline-success" target="_blank" href="https://api.whatsapp.com/send?phone={{ cleanDisplayPhoneNumber($whatsappPhoneNumber['display_phone_number']) }}"><i class="fab fa-whatsapp"></i>  {{ __tr('WhatsApp Now') }}</a>
                </div>
            </div>
        </div>
        </fieldset>
        @endforeach
        @else
        <div class="alert alert-info">{{  __tr('Please resync phone numbers.') }}</div>
        @endif
        @else
        <div class="text-danger">
            {{  __tr('Phone number does not configured yet.') }}
        </div>
        @endif
    </x-lw.modal>
    {{-- /QR CODE model --}}
    <template x-if="!disableSoundForMessageNotification">
        <audio id="lwMessageAlertTone">
            <source src="<?= asset('/static-assets/audio/whatsapp-notification-tone.mp3'); ?>" type="audio/mpeg">
        </audio>
     </template>
    @endif
    <script>
        (function($) {
            'use strict';
            window.appConfig = {
                debug: "{{ config('app.debug') }}",
                csrf_token: "{{ csrf_token() }}",
                locale : '{{ app()->getLocale() }}',
                vendorUid : '{{ getVendorUid() }}',
                broadcast_connection_driver: "{{ getAppSettings('broadcast_connection_driver') }}",
                pusher : {
                    key : "{{ config('broadcasting.connections.pusher.key') }}",
                    cluster : "{{ config('broadcasting.connections.pusher.options.cluster') }}",
                    host : "{{ config('broadcasting.connections.pusher.options.host') }}",
                    port : "{{ config('broadcasting.connections.pusher.options.port') }}",
                    useTLS : "{{ config('broadcasting.connections.pusher.options.useTLS') }}",
                    encrypted : "{{ config('broadcasting.connections.pusher.options.encrypted') }}",
                    authEndpoint : "{{ url('/broadcasting/auth') }}"
                },
            }
        })(jQuery);
    </script>
    <?= __yesset(
        [
            'dist/js/jsware.js',
            'dist/js/app.js',
            // keep it last
            'dist/js/alpinejs.min.js',
        ],
        true,
    ) ?>
    @if(hasVendorAccess() or hasVendorUserAccess())
    {{-- app bootstrap --}}
    {!! __yesset('dist/js/bootstrap.js', true) !!}
    @endif
    @stack('vendorLibs')
    <script src="{{ route('vendor.load_server_compiled_js') }}"></script>
    @stack('footer')
    @stack('appScripts')
    <script>
    (function($) {
        'use strict';
        @if (session('alertMessage'))
            showAlert("{{ session('alertMessage') }}", "{{ session('alertMessageType') ?? 'info' }}");
            @php
                session('alertMessage', null);
                session('alertMessageType', null);
            @endphp
        @endif
        @php
        $isRestrictedVendorUser = (!hasVendorAccess() ? hasVendorAccess('assigned_chats_only') : false);
        @endphp
        var isRestrictedVendorUser = {{ $isRestrictedVendorUser ? 1 : 0 }},
            loggedInUserId = '{{ getUserId() }}';
        __Utils.setTranslation({
            'processing': "{{ __tr('processing') }}",
            'uploader_default_text': "<span class='filepond--label-action'>{{ __tr('Drag & Drop Files or Browse') }}</span>",
            "confirmation_yes": "{{ __tr('Yes') }}",
            "confirmation_no": "{{ __tr('No') }}"
        });

        @if(hasVendorAccess() or hasVendorUserAccess())
            var broadcastActionDebounce,
                campaignActionDebounce,
                lastEventData,
                lastCampaignStatus;
            window.Echo.private(`vendor-channel.${window.appConfig.vendorUid}`).listen('.VendorChannelBroadcast', function (data) {
                // if the event data matched does not need to process it
                if(_.isEqual(lastEventData, data)) {
                    return true;
                }
                if(!data.campaignUid && (!isRestrictedVendorUser || (isRestrictedVendorUser && (data.assignedUserId == loggedInUserId)))) {
                    // chat updates
                    if(data.contactUid && $('[data-contact-uid=' + data.contactUid + ']').length) {
                        __DataRequest.get(__Utils.apiURL("{{ route('vendor.chat_message.data.read', ['contactUid', 'way']) }}{{ ((isset($assigned) and $assigned) ? '?assigned=to-me' : '') }}", {'contactUid': data.contactUid, 'way':'prepend'}),{}, function(responseData) {
                            __DataRequest.updateModels({
                                '@whatsappMessageLogs' : 'append',
                                'whatsappMessageLogs':responseData.client_models.whatsappMessageLogs
                            });
                            window.lwScrollTo('#lwEndOfChats', true);
                        });
                    } else if((!isRestrictedVendorUser || (isRestrictedVendorUser && (data.assignedUserId == loggedInUserId)))) {
                        // play the sound for incoming message notifications
                        if(data.isNewIncomingMessage && $('#lwMessageAlertTone').length) {
                            $('#lwMessageAlertTone')[0].play();
                        };
                    };
                }
                lastEventData = _.cloneDeep(data);
                clearTimeout(broadcastActionDebounce);
                broadcastActionDebounce = setTimeout(function() {
                    // generic model updates
                    if(data.eventModelUpdate) {
                        __DataRequest.updateModels(data.eventModelUpdate);
                    }
                    @if(hasVendorAccess('messaging'))
                    if(!data.campaignUid && (!isRestrictedVendorUser || (isRestrictedVendorUser && (data.assignedUserId == loggedInUserId)))) {
                        // is incoming message
                        if(data.isNewIncomingMessage) {
                            __DataRequest.get("{{ route('vendor.chat_message.read.unread_count') }}",{}, function(responseData) {});
                        };
                        // contact list update
                        if($('.lw-whatsapp-chat-window').length) {
                            __DataRequest.get(__Utils.apiURL("{!! route('vendor.contacts.data.read', ['contactUid','way' => 'append','request_contact' => '', 'assigned'=> ($assigned ?? '')]); !!}", {'contactUid': $('#lwWhatsAppChatWindow').data('contact-uid'),'request_contact' : 'request_contact=' + data.contactUid + '&'}),{}, function() {});
                        }
                    }
                    @endif
                }, 1000);
                @if(hasVendorAccess('messaging'))
                // 10 seconds for campaign
                    clearTimeout(campaignActionDebounce);
                    campaignActionDebounce = setTimeout(function() {
                        // campaign data update
                        if(data.campaignUid && $('.lw-campaign-window-' + data.campaignUid).length) {
                            __DataRequest.get(__Utils.apiURL("{{ route('vendor.campaign.status.data', ['campaignUid']) }}", {'campaignUid': data.campaignUid}),{}, function(responseData) {
                                if(responseData.data.campaignStatus != lastCampaignStatus) {
                                    window.reloadDT('#lwCampaignQueueLog');
                                }
                                lastCampaignStatus = responseData.data.campaignStatus;
                            });
                        };
                    }, 10000);
                @endif
            });
        @if(hasVendorAccess('messaging'))
        // initially get the unread count on page loads
        __DataRequest.get("{{ route('vendor.chat_message.read.unread_count') }}",{}, function() {});
        @endif
    @endif
    })(jQuery);
    </script>

    <!-- Pusher Beams Registration Script -->
	<script>
	  const beamsClient = new PusherPushNotifications.Client({
		instanceId: '{{ config('services.pusher_beams.instance_id') }}',
	  });

	  beamsClient.start()
		.then(() => {
		  // Obtener el vendorId y userId desde Laravel
		  const vendorId = '{{ getVendorId() }}';
		  const userId = '{{ getUserId() }}';
		  const interestVendor = `vendor_${vendorId}`;
		  const interestUser = `user_${userId}`;

		  // Agregar el interés del vendor
		  return beamsClient.addDeviceInterest(interestVendor)
			.then(() => {
			  // Agregar el interés del usuario después de agregar el del vendor
			  return beamsClient.addDeviceInterest(interestUser);
			});
		})
		.then(() => {
		  console.log('¡Registrado y suscrito exitosamente a ambos intereses!');
		})
		.catch(error => {
		  console.error('Error al registrar intereses en Pusher Beams:', error);
		});
	</script>

    {!! getAppSettings('page_footer_code_all') !!}
    @if(isLoggedIn())
    {!! getAppSettings('page_footer_code_logged_user_only') !!}
    @endif
</body>
</html>

<!-- Modal: Lista de Formularios -->
<div class="modal fade" id="customFormsModal" tabindex="-1" role="dialog" aria-labelledby="customFormsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

      <!-- Encabezado del Modal -->
      <div class="modal-header">
        <h5 class="modal-title" id="customFormsModalLabel">{{ __tr('Formularios Disponibles') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __tr('Cerrar') }}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- Cuerpo del Modal: Listado de Formularios -->
      <div class="modal-body">
         @php
            // Obtener datos necesarios del proveedor y del usuario autenticado
            $vendorId = getVendorId();
            $user = auth()->user();

            if ($user) {
                $userId = $user->getKey(); // Asumiendo que 'getKey()' retorna el ID del usuario
                $userUid = $user->_uid ?? '';
                $username = $user->username ?? '';
                $email = $user->email ?? '';
                $firstName = $user->first_name ?? '';
                $lastName = $user->last_name ?? '';
                $mobileNumber = $user->mobile_number ?? '';
                $vendorUserId = $user->vendors__id ?? '';
            } else {
                // Manejar el caso donde el usuario no está autenticado
                $userId = null;
                $userUid = '';
                $username = '';
                $email = '';
                $firstName = '';
                $lastName = '';
                $mobileNumber = '';
                $vendorUserId = '';
            }

            // Recorremos desde 0 hasta 9 (10 formularios)
            $forms = [];
            for ($i = 0; $i < 10; $i++) {
                $embedUrl = getVendorSettings('form_vendor_contact_embed_' . $i);
                $formName = getVendorSettings('form_vendor_contact_name_' . $i);

                // Si hay configuración para ambos, añadimos al array
                if (!empty($embedUrl) && !empty($formName)) {
                    // Agregar parámetros GET a la URL del formulario
                    $queryParams = [
                        //'vendor_id'      => $vendorId,
                        'user_id'        => $userId,
                        //'user_uid'       => $userUid,
                        //'username'       => $username,
                        //'email'          => $email,
                        //'first_name'     => $firstName,
                        //'last_name'      => $lastName,
                        //'mobile_number'  => $mobileNumber,
                        //'vendor_user_id' => $vendorUserId,
                        // Agrega otros parámetros según sea necesario
                    ];

                    // Filtrar parámetros que no sean nulos o vacíos
                    $queryParams = array_filter($queryParams, function($value) {
                        return !is_null($value) && $value !== '';
                    });

                    // Verificar si la URL ya tiene parámetros
                    if (parse_url($embedUrl, PHP_URL_QUERY)) {
                        $fullUrl = $embedUrl . '&' . http_build_query($queryParams);
                    } else {
                        $fullUrl = $embedUrl . '?' . http_build_query($queryParams);
                    }

                    // Escapar los datos para uso en atributos de datos
                    $escapedFullUrl = e($fullUrl);
                    $escapedFormName = e($formName);

                    $forms[] = [
                        'embedUrl' => $escapedFullUrl,
                        'name'     => $escapedFormName
                    ];
                }
            }
         @endphp

         @if(!empty($forms))
            <ul class="list-group">
              @foreach($forms as $form)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>{{ $form['name'] }}</span>
                  <!-- Botones para Abrir, Copiar Enlace y Abrir en Nueva Pestaña -->
                  <div>
                    <!-- Botón para Abrir el Formulario en iFrame -->
                    <button 
                      class="btn btn-sm btn-outline-info" 
                      data-url="{{ $form['embedUrl'] }}" 
                      data-name="{{ $form['name'] }}" 
                      onclick="showFormEmbedPopup(this.dataset.url, this.dataset.name)">
                      <i class="fa fa-eye"></i> {{ __tr('Abrir') }}
                    </button>
                    <!-- Botón para Copiar el Enlace del Formulario -->
                    <button 
                      class="btn btn-sm btn-outline-secondary ml-2" 
                      data-url="{{ $form['embedUrl'] }}" 
                      onclick="copyFormURL(this.dataset.url)">
                      <i class="fa fa-copy"></i> {{ __tr('Copiar Enlace') }}
                    </button>
                    <!-- Botón para Abrir el Formulario en Nueva Pestaña -->
                    <button 
                      class="btn btn-sm btn-outline-primary ml-2" 
                      data-url="{{ $form['embedUrl'] }}" 
                      onclick="launchFormInNewTab(this.dataset.url)">
                      <i class="fa fa-external-link-alt"></i> {{ __tr('Abrir en Nueva Pestaña') }}
                    </button>
                  </div>
                </li>
              @endforeach
            </ul>
         @else
            <div class="alert alert-info">
              {{ __tr('No hay formularios configurados.') }}
            </div>
         @endif
      </div>

      <!-- Footer del Modal -->
      <div class="modal-footer">
        <!-- Botón para Administrar Formularios -->
        <a href="https://forms.alfabusiness.app/forms" target="_blank" class="btn btn-primary w-50">
          <i class="fa fa-external-link-alt"></i> {{ __tr('Administrar Formularios') }}
        </a>
        <!-- Botón para Cerrar el Modal -->
        <button type="button" class="btn btn-secondary w-50" data-dismiss="modal">{{ __tr('Cerrar') }}</button>
      </div>

    </div>
  </div>
</div>

<!-- Modal: Mostrar Formulario en iFrame grande -->
<div class="modal fade" id="customFormEmbedModal" tabindex="-1" role="dialog" aria-labelledby="customFormEmbedModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width:90vw;" role="document">
    <div class="modal-content" style="height:90vh;">

      <!-- Encabezado del Modal -->
      <div class="modal-header">
        <h5 class="modal-title" id="customFormEmbedModalLabel">{{ __tr('Formulario') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __tr('Cerrar') }}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- Cuerpo del Modal: el iframe ocupa todo el espacio disponible -->
      <div class="modal-body p-0" style="height:calc(90vh - 56px);">
        <iframe id="customFormEmbedIframe" 
                src="" 
                style="border:0; width:100%; height:100%;"
                sandbox="allow-forms allow-scripts allow-same-origin"
                allowfullscreen>
        </iframe>
      </div>

    </div>
  </div>
</div>

<script>
  /**
   * Abre el modal #customFormEmbedModal y ajusta el src del iframe con la URL del formulario.
   * @param {String} urlForm - La URL completa del formulario con parámetros GET.
   * @param {String} titleForm - El título del formulario (opcional).
   */
  function showFormEmbedPopup(urlForm, titleForm) {
    // Asignar la URL al iframe
    const iframeElement = document.getElementById('customFormEmbedIframe');
    if (iframeElement) {
      iframeElement.src = urlForm; // Ahora incluye parámetros GET
    } else {
      console.error("No se encontró el iframe con el ID 'customFormEmbedIframe'.");
      alert("Error al cargar el formulario. Inténtalo de nuevo.");
      return;
    }

    // Cambiar el título del modal dinámicamente (opcional)
    const modalTitle = document.getElementById('customFormEmbedModalLabel');
    if (modalTitle) {
      modalTitle.innerText = titleForm || '{{ __tr('Formulario') }}';
    } else {
      console.warn("No se encontró el elemento con el ID 'customFormEmbedModalLabel' para actualizar el título.");
    }

    // Mostrar el modal usando jQuery
    $('#customFormEmbedModal').modal('show');
  }

  /**
   * Copia el enlace del formulario al portapapeles.
   * @param {String} urlForm - La URL completa del formulario con parámetros GET.
   */
  function copyFormURL(urlForm) {
    // Crear un elemento temporal para copiar el texto
    const inputTemp = document.createElement('input');
    inputTemp.value = urlForm;
    document.body.appendChild(inputTemp);
    inputTemp.select();
    inputTemp.setSelectionRange(0, 99999); // Para dispositivos móviles

    try {
      document.execCommand('copy');
      alert("Enlace del formulario copiado al portapapeles.");
    } catch (err) {
      console.error('Error al copiar el enlace: ', err);
      alert("No se pudo copiar el enlace. Intenta manualmente.");
    }

    document.body.removeChild(inputTemp);
  }

  /**
   * Abre el formulario en una nueva pestaña del navegador.
   * @param {String} urlForm - La URL completa del formulario con parámetros GET.
   */
  function launchFormInNewTab(urlForm) {
    // Abrir la URL en una nueva pestaña
    window.open(urlForm, '_blank');
  }

  /**
   * Limpiar el iframe al cerrar el modal para evitar la reproducción continua
   */
  $('#customFormEmbedModal').on('hidden.bs.modal', function () {
    const iframeElement = document.getElementById('customFormEmbedIframe');
    if (iframeElement) {
      iframeElement.src = '';
    }
  });
</script>