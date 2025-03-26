@extends('layouts.app', ['class' => 'main-content-has-bg'])
@section('content')
@include('layouts.headers.guest')
<div class="container my-5 lw-terms-and-conditions-page">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0">
                <div class="card-header text-center bg-primary text-white">
                    <h1 class="mb-0">TÉRMINOS Y CONDICIONES</h1>
                    <small>Última actualización: {{ $data['updated_at'] }}</small>
                </div>
                <div class="card-body px-lg-5 py-lg-5">
                    

                    <!-- Contenido -->
                    <div class="content">
                        <section id="identificacion" class="mb-4">
                            <h2>1. IDENTIFICACIÓN DEL RESPONSABLE DEL TRATAMIENTO</h2>
                            <p>
                                Estos Términos y Condiciones regulan el uso del servicio que se presta a través de la plataforma [crm.alfabusiness.app]. La Empresa, en adelante “la Empresa Usuaria”, es la única responsable del tratamiento de los datos que proporciona y gestiona en su sitio web, conforme a lo establecido en la Ley Orgánica de Protección de Datos Personales de Ecuador y demás normativas vigentes.
                            </p>
                            <p>
                                <strong>Responsable del Tratamiento:</strong><br>
                                Nombre Legal: ALFA BUSINESS PLANET ALFA BP S.A.S<br>
                                Correo electrónico de contacto: info@alfabusiness.app<br>
                                <em>Nota: ALFA BUSINESS APP es el sistema CRM que la Empresa utiliza para gestionar sus datos y no asume responsabilidad alguna sobre el tratamiento, compartición o veracidad de dichos datos.</em>
                            </p>
                        </section>
                        
                        <section id="datos-empresa" class="mb-4">
                            <h2>2. DATOS DE LA EMPRESA</h2>
                            <p>
                                La Empresa Usuaria debe proporcionar la siguiente información, que se integrará en sus Términos y Condiciones y se utilizará para fines de identificación, comunicación, administración y facturación:
                            </p>
                            <ul>
                                <li><strong>Datos de la Empresa:</strong> [Vendor Title]</li>
                                <li><strong>Información Comercial:</strong> [Business Information]</li>
                                <li><strong>Dirección y Contacto:</strong> [Address & Contact]</li>
                                <li><strong>Dirección:</strong> [Address line]</li>
                                <li><strong>Código Postal:</strong> [Postal Code]</li>
                                <li><strong>Ciudad:</strong> [City]</li>
                                <li><strong>Provincia/Estado:</strong> [State]</li>
                                <li><strong>País:</strong> [Select Country]</li>
                                <li><strong>Teléfono Comercial:</strong> [Business Phone]</li>
                                <li><strong>Correo Electrónico de Contacto:</strong> [Contact Email]</li>
                            </ul>
                        </section>
                        
                        <section id="objeto" class="mb-4">
                            <h2>3. OBJETO DEL SERVICIO</h2>
                            <p>
                                El presente documento establece los Términos y Condiciones que rigen la relación contractual entre la Empresa Usuaria y sus clientes en el marco del uso del servicio. Dicho servicio se orienta a la gestión integral de datos e información, permitiendo la administración, seguimiento y análisis de las operaciones de la Empresa.
                            </p>
                            <p>
                                La Empresa utiliza la plataforma para gestionar sus actividades, garantizando que el tratamiento de datos se realice de acuerdo con las finalidades expresadas y cumpliendo con la normativa de protección de datos personales.
                            </p>
                        </section>
                        
                        <section id="obligaciones" class="mb-4">
                            <h2>4. OBLIGACIONES DE LA EMPRESA USUARIA</h2>
                            <p>
                                La Empresa Usuaria, como responsable del tratamiento, se compromete a:
                            </p>
                            <ul>
                                <li><strong>Exactitud y Actualización:</strong> Garantizar que la información proporcionada es correcta, completa y se mantendrá actualizada.</li>
                                <li><strong>Consentimiento y Uso de Datos:</strong> Otorgar el consentimiento para el tratamiento de sus datos de conformidad con estos Términos y con la Política de Protección de Datos publicada en su sitio web.</li>
                                <li><strong>Uso Adecuado del Servicio:</strong> Utilizar la plataforma de manera lícita y para los fines previstos en el contrato, absteniéndose de realizar actividades que comprometan la seguridad, integridad o disponibilidad del servicio.</li>
                                <li><strong>Transparencia y Comunicación:</strong> Informar a sus clientes y terceros sobre el tratamiento de los datos, facilitando el ejercicio de los derechos de acceso, rectificación, cancelación, oposición y portabilidad.</li>
                            </ul>
                        </section>
                        
                        <section id="proteccion" class="mb-4">
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
                        
                        <section id="cookies" class="mb-4">
                            <h2>6. COOKIES Y TECNOLOGÍAS DE SEGUIMIENTO</h2>
                            <p>
                                El sitio web de la Empresa utiliza cookies y tecnologías similares (incluyendo cookies de terceros como Google Analytics, Meta Pixel y API de conversiones) para:
                            </p>
                            <ul>
                                <li>Mejorar la experiencia de navegación.</li>
                                <li>Recopilar información estadística sobre el uso del sitio.</li>
                                <li>Optimizar campañas publicitarias y la interacción con el usuario.</li>
                            </ul>
                            <p>
                                Los usuarios pueden gestionar sus preferencias de cookies a través de la configuración de su navegador, conforme a lo establecido en la Política de Cookies.
                            </p>
                        </section>
                        
                        <section id="propiedad" class="mb-4">
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
                        
                        <section id="modificaciones" class="mb-4">
                            <h2>8. MODIFICACIONES A LOS TÉRMINOS Y CONDICIONES</h2>
                            <p>
                                La Empresa se reserva el derecho de modificar estos Términos y Condiciones en cualquier momento. Las modificaciones se publicarán en el sitio web y, cuando sean relevantes, se notificará a los usuarios mediante el correo electrónico [Contact Email]. El uso continuado del servicio implicará la aceptación de los cambios realizados.
                            </p>
                        </section>
                        
                        <section id="legislacion" class="mb-4">
                            <h2>9. LEGISLACIÓN APLICABLE Y JURISDICCIÓN</h2>
                            <p>
                                Estos Términos y Condiciones se regirán por las leyes de la República del Ecuador. En caso de controversia, ambas partes se someten a la jurisdicción de los tribunales competentes en Ecuador.
                            </p>
                        </section>
                        
                        <section id="contacto" class="mb-4">
                            <h2>10. CONTACTO</h2>
                            <p>
                                Para cualquier consulta, solicitud o ejercicio de derechos en materia de protección de datos, la Empresa puede ser contactada a través de:
                            </p>
                            <ul>
                                <li><strong>Correo electrónico:</strong> [Contact Email]</li>
                                <li><strong>Teléfono:</strong> [Business Phone]</li>
                                <li><strong>Dirección:</strong> [Address & Contact], [Address line], [City], [State], [Select Country]</li>
                            </ul>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
