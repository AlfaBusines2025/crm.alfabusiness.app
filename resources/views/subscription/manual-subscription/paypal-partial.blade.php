@if ($paymentMethod == 'paypal' and ($subscriptionRequestRecord->status == 'initiated'))
    @if (getAppSettings('enable_paypal'))
        @if (getAppSettings('use_test_paypal_checkout'))
            <script
                src="https://www.paypal.com/sdk/js?client-id=<?= getAppSettings('paypal_checkout_testing_publishable_key') ?>&currency=<?= getAppSettings('currency') ?>&vault=true&intent=subscription">
            </script>
        @else
            <script
                src="https://www.paypal.com/sdk/js?client-id=<?= getAppSettings('paypal_checkout_live_publishable_key') ?>&currency=<?= getAppSettings('currency') ?>&vault=true&intent=subscription">
            </script>
        @endif
    @endif

    @php
        // Mapeo de planes a sus respectivos plan_ids de PayPal
		//plan de prueba de 1 $ con free trial P-8X691876PJ962752LM7RSSEY
		//plan original mensual omega P-9UU52465EK714674CM7RMXCQ
        $paypalSubscriptions = [
            '1' => [
                'monthly' => 'P-9UU52465EK714674CM7RMXCQ', 
                'yearly'  => 'P-1U187013XJ919474NM7RNTPI'
            ],
            '2' => [
                'monthly' => 'P-0X736510S8899935YM7RNK2I',
                'yearly'  => 'P-83C31091WS702843GM7RNUHA'
            ],
            '3' => [
                'monthly' => 'P-806382790J962635JM7RNOWA',
                'yearly'  => 'P-55W85491GW9355715M7RNVWA'
            ],
        ];

        // Extraemos el número del plan (por ejemplo, 'plan_1' se convierte en '1')
        $currentPlanId = isset($planDetails['id']) ? str_replace('plan_', '', $planDetails['id']) : null;
        // Se espera que en el registro se guarde la frecuencia seleccionada; si no, se asume 'Mensual'
        $selectedFrequency = $planFrequencyTitle;
        // Obtenemos el ID de plan de PayPal según la selección
        $paypalPlanId = $currentPlanId && isset($paypalSubscriptions[$currentPlanId][$selectedFrequency])
                        ? $paypalSubscriptions[$currentPlanId][$selectedFrequency]
                        : null;
    @endphp

    @push('appScripts')
        <script type="text/javascript">
            (function() {
                'use strict';
                try {
                    var manualSubscriptionUid = "{{ $subscriptionRequestRecord->_uid }}";
                    paypal.Buttons({
                        style: {
                            shape: 'pill',
                            color: 'blue',
                            layout: 'vertical',
                            label: 'subscribe'
                        },
                        // Crear la suscripción en PayPal
                        createSubscription: function(data, actions) {
                            return actions.subscription.create({
                                plan_id: '{{ $paypalPlanId }}'
                            });
                        },
                        // Una vez aprobada la suscripción, redirigimos al usuario
                        onApprove: function(data, actions) {
                            console.log('Subscription ID: ' + data.subscriptionID);
							 console.log('Data Subscription ID: ' + JSON.stringify(data, null, 2));
							
                            //window.location.href = '{{ route("subscription.read.show") }}' + '?subscriptionID=' + data.subscriptionID + '&manualSubscriptionUid=' + manualSubscriptionUid ;
							
							return fetch("{{ route('capture.paypal.checkout') }}", {
								method: "POST",
								headers: {
									'Content-Type': 'application/json',
									'X-CSRF-TOKEN': '{{csrf_token() }}',
								},
								body: JSON.stringify({
									"suscriptionID": data.subscriptionID,
									"orderUID": data.orderID,
									"manualSubscriptionUid": manualSubscriptionUid,
								})
							})
							.then((response) => {
								return response.json();
							})
							.then((orderData) => {
								// Successful capture! For dev/demo purposes:
								//window.location = orderData.data.redirectRoute;
								window.location.href = '{{ route("subscription.read.show") }}' + '?subscriptionID=' + data.subscriptionID + '&manualSubscriptionUid=' + manualSubscriptionUid ;
							});
							
                        },
                        onError: function(err) {
                            showAlert(JSON.stringify(err.message, null, 2), 'error');
							//console.log(JSON.stringify(err.message, null, 2));
                        },
                        onCancel: function(data) {
                            showAlert("{{ __tr('User cancelled payment.') }}", 'error');
                        }
                    }).render('#paypal-button-container');
                } catch (error) {
                    if ('{{ getAppSettings("enable_paypal") }}') {
                        showAlert(error, 'error');
                    }
                }
            })();
        </script>
    @endpush
@endif