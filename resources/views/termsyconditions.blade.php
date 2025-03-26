@extends('layouts.app', ['class' => 'main-content-has-bg'])
@section('content')
@include('layouts.headers.guest')
<div class="container lw-guest-page-container-block pb-2 lw-terms-and-conditions-page">
    <div class="row justify-content-center">
        <div class="col-lg-12 col-md-8">
            <div class="card shadow border-0">
                <h1 class="card-header text-center">TÉRMINOS Y CONDICIONES</h1>
                <div class="card-body px-lg-5 py-lg-5 p lw-ws-pre-line">
                    <header>
                        <p>Última actualización: [Fecha]</p>
                    </header>
                    <main>
                        <section>
                            <h2>1. IDENTIFICACIÓN DEL RESPONSABLE DEL TRATAMIENTO</h2>
                            <p>
                                Estos Términos y Condiciones regulan el uso del servicio que se presta a través de la plataforma <strong>crm.alfabusiness.app</strong>. La Empresa, en adelante “la Empresa Usuaria”, es la única responsable del tratamiento de los datos que proporciona y gestiona en su sitio web, conforme a lo establecido en la Ley Orgánica de Protección de Datos Personales de Ecuador y demás normativas vigentes.
                            </p>
                            <p>
                                <strong>Responsable del Tratamiento:</strong><br>
                                Nombre Legal: ALFA BUSINESS PLANET ALFA BP S.A.S<br>
                                Correo electrónico de contacto: info@alfabusiness.app<br>
                                <strong>Nota:</strong> ALFA BUSINESS APP es el sistema CRM que la Empresa utiliza para gestionar sus datos y no asume responsabilidad alguna sobre el tratamiento, compartición o veracidad de dichos datos.
                            </p>
                        </section>
                        <section>
                            <h2>2. DATOS DE LA EMPRESA</h2>
                            <p>
                                La Empresa Usuaria debe proporcionar la siguiente información, que se integrará en sus Términos y Condiciones y se utilizará para fines de identificación, comunicación, administración y facturación:
                            </p>
                            <ul>
                                <li>Datos de la Empresa: {{ $data['vendor_title'] }}</li>
                                <li>Información Comercial: {{ $data['business_information'] }}</li>
                                <li>Dirección y Contacto: {{ $data['address_and_contact'] }}</li>
                                <li>Dirección: {{ $data['address_line'] }}</li>
                                <li>Código Postal: {{ $data['postal_code'] }}</li>
                                <li>Ciudad: {{ $data['city'] }}</li>
                                <li>Provincia/Estado: {{ $data['state'] }}</li>
                                <li>País: {{ $data['select_country'] }}</li>
                                <li>Teléfono Comercial: {{ $data['business_phone'] }}</li>
                                <li>Correo Electrónico de Contacto: {{ $data['contact_email'] }}</li>
                            </ul>
                        </section>
                        <section>
                            <h2>3. OBJETO DEL SERVICIO</h2>
                            <p>
                                El presente documento establece los Términos y Condiciones que rigen la relación contractual entre la Empresa Usuaria y sus clientes en el marco del uso del servicio. Dicho servicio se orienta a la gestión integral de datos e información, permitiendo la administración, seguimiento y análisis de las operaciones de la Empresa.
                            </p>
                            <p>
                                La Empresa utiliza la plataforma para gestionar sus actividades, garantizando que el tratamiento de datos se realice de acuerdo con las finalidades expresadas y cumpliendo con la normativa de protección de datos personales.
                            </p>
                        </section>
                        <section>
                            <h2>4. OBLIGACIONES DE LA EMPRESA USUARIA</h2>
                            <p>
                                La Empresa Usuaria, como responsable del tratamiento, se compromete a:
                            </p>
                            <ul>
                                <li>
                                    <strong>Exactitud y Actualización:</strong> Garantizar que la información proporcionada es correcta, completa y se mantendrá actualizada.
                                </li>
                                <li>
                                    <strong>Consentimiento y Uso de Datos:</strong> Otorgar el consentimiento para el tratamiento de sus datos de conformidad con estos Términos y con la Política de Protección de Datos publicada en su sitio web.
                                </li>
                                <li>
                                    <strong>Uso Adecuado del Servicio:</strong> Utilizar la plataforma de manera lícita y para los fines previstos en el contrato, absteniéndose de realizar actividades que comprometan la seguridad, integridad o disponibilidad del servicio.
                                </li>
                                <li>
                                    <strong>Transparencia y Comunicación:</strong> Informar a sus clientes y terceros sobre el tratamiento de los datos, facilitando el ejercicio de los derechos de acceso, rectificación, cancelación, oposición y portabilidad.
                                </li>
                            </ul>
                        </section>
                        <section>
                            <h2>5. PROTECCIÓN DE DATOS PERSONALES</h2>
                            <ul>
                                <li>
                                    <strong>Marco Legal:</strong> El tratamiento de los datos se realizará en estricto cumplimiento de la Ley Orgánica de Protección de Datos Personales de Ecuador y demás normativas aplicables. Dicho tratamiento se basará en el consentimiento informado o en otras bases legales permitidas.
                                </li>
                                <li>
                                    <strong>Medidas de Seguridad:</strong> La Empresa implementará medidas técnicas, organizativas y jurídicas adecuadas para proteger la información, tales como protocolos de encriptación, controles de acceso y auditorías periódicas.
                                </li>
                                <li>
                                    <strong>Derechos de los Titulares:</strong> Los titulares de los datos (incluida la Empresa y sus clientes) podrán ejercer sus derechos de acceso, rectificación, cancelación, oposición y portabilidad, a través de los canales establecidos en la Política de Protección de Datos.
                                </li>
                            </ul>
                        </section>
                        <section>
                            <h2>6. COOKIES Y TECNOLOGÍAS DE SEGUIMIENTO</h2>
                            <ul>
                                <li>Mejorar la experiencia de navegación.</li>
                                <li>Recopilar información estadística sobre el uso del sitio.</li>
                                <li>Optimizar campañas publicitarias y la interacción con el usuario.</li>
                            </ul>
                            <p>
                                Los usuarios pueden gestionar sus preferencias de cookies a través de la configuración de su navegador, conforme a lo establecido en la Política de Cookies.
                            </p>
                        </section>
                        <section>
                            <h2>7. PROPIEDAD INTELECTUAL Y LIMITACIÓN DE RESPONSABILIDAD</h2>
                            <ul>
                                <li>
                                    <strong>Propiedad Intelectual:</strong> Los derechos de propiedad intelectual sobre el software, la plataforma y sus contenidos son propiedad exclusiva de ALFA BUSINESS PLANET ALFA BP S.A.S. La Empresa Usuaria se compromete a respetar dichos derechos y a no reproducir, distribuir ni modificar el contenido sin autorización expresa.
                                </li>
                                <li>
                                    <strong>Exoneración de Responsabilidad:</strong> En lo que respecta a la compartición de datos con terceros (tales como proveedores, socios tecnológicos o cualquier otro destinatario autorizado), la Empresa Usuaria asume la responsabilidad exclusiva del tratamiento y gestión de sus datos. En consecuencia, ALFA BUSINESS APP queda eximida de toda responsabilidad por el uso o compartición de los datos proporcionados, siendo la Empresa quien debe gestionar cualquier riesgo, obligación o eventualidad derivada de dicho tratamiento.
                                </li>
                            </ul>
                        </section>
                        <section>
                            <h2>8. MODIFICACIONES A LOS TÉRMINOS Y CONDICIONES</h2>
                            <p>
                                La Empresa se reserva el derecho de modificar estos Términos y Condiciones en cualquier momento. Las modificaciones se publicarán en el sitio web y, cuando sean relevantes, se notificará a los usuarios mediante el correo electrónico {{ $data['contact_email'] }}. El uso continuado del servicio implicará la aceptación de los cambios realizados.
                            </p>
                        </section>
                        <section>
                            <h2>9. LEGISLACIÓN APLICABLE Y JURISDICCIÓN</h2>
                            <p>
                                Estos Términos y Condiciones se regirán por las leyes de la República del Ecuador. En caso de controversia, ambas partes se someten a la jurisdicción de los tribunales competentes en Ecuador.
                            </p>
                        </section>
                        <section>
                            <h2>10. CONTACTO</h2>
                            <ul>
                                <li>Correo electrónico: {{ $data['contact_email'] }}</li>
                                <li>Teléfono: {{ $data['business_phone'] }}</li>
                                <li>
                                    Dirección: 
                                    {{ $data['address_and_contact'] }}, 
                                    {{ $data['address_line'] }}, 
                                    {{ $data['city'] }}, 
                                    {{ $data['state'] }}, 
                                    {{ $data['select_country'] }}
                                </li>
                            </ul>
                        </section>
                    </main>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
