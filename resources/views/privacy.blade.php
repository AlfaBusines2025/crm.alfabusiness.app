@extends('layouts.app', ['class' => 'main-content-has-bg'])
@section('content')
@include('layouts.headers.guest')
<div class="container lw-guest-page-container-block pb-2 lw-terms-and-conditions-page">
    <div class="row justify-content-center">
        <div class="col-lg-12 col-md-8">
            <div class="card shadow border-0">
                <h1 class="card-header text-center">Política de Privacidad</h1>
                <div class="card-body px-lg-5 py-lg-5 p lw-ws-pre-line">
                    <header>
                        <p>Última actualización: [Fecha]</p>
                    </header>

                    <main>
                        <section>
                            <h2>1. INTRODUCCIÓN</h2>
                            <p>
                                En {{ $data['vendor_title'] }}, nos comprometemos a proteger la privacidad y la seguridad de los datos personales que recopilamos y tratamos, garantizando el ejercicio del derecho a la protección de datos de conformidad con la Ley Orgánica de Protección de Datos Personales de Ecuador y su reglamento. Esta Política de Privacidad describe de manera clara y transparente cómo se recogen, utilizan, comparten y protegen los datos de carácter personal de nuestros clientes, proveedores y empleados, así como de la información corporativa que nos proporciona la Empresa Usuaria.
                            </p>
                        </section>

                        <section>
                            <h2>2. RESPONSABLE DEL TRATAMIENTO</h2>
                            <p><strong>Nombre Legal:</strong> ALFA BUSINESS PLANET ALFA BP S.A.S</p>
                            <p><strong>Correo electrónico de contacto:</strong> info@alfabusiness.app</p>
                            <p>
                                <strong>Nota:</strong> ALFA BUSINESS APP es el sistema CRM que la Empresa utiliza para gestionar sus datos corporativos. La Empresa Usuaria es la única responsable del tratamiento y la veracidad de la información que introduce en el sistema.
                            </p>
                        </section>

                        <section>
                            <h2>3. DATOS PERSONALES RECOPILADOS</h2>
                            <h3>3.1. Datos de la Empresa</h3>
                            <p>
                                Estos datos se recogen para identificar y gestionar la relación comercial y contractual, e incluyen:
                            </p>
                            <ul>
                                <li>Datos de la Empresa: {{ $data['vendor_title'] }}</li>
                                <li>Información Comercial: {{ $data['business_information'] }}</li>
                                <li>
                                    Dirección y Contacto: 
                                    {{ $data['address_and_contact'] }}, 
                                    {{ $data['address_line'] }}, 
                                    {{ $data['postal_code'] }}, 
                                    {{ $data['city'] }}, 
                                    {{ $data['state'] }}, 
                                    {{ $data['select_country'] }}
                                </li>
                                <li>Teléfono Comercial: {{ $data['business_phone'] }}</li>
                                <li>Correo Electrónico: {{ $data['contact_email'] }}</li>
                            </ul>
                            <p>Otros: {{ $data['other'] }}</p>
                            
                            <h3>3.2. Datos de Uso y Navegación</h3>
                            <p>
                                Durante el uso de nuestro sitio web y servicios, recopilamos información adicional mediante:
                            </p>
                            <ul>
                                <li>
                                    <strong>Cookies y Tecnologías de Seguimiento:</strong> Utilizamos cookies propias y de terceros (como Google Analytics, Meta Pixel y API de conversiones) para mejorar la experiencia del usuario, analizar la interacción y optimizar campañas publicitarias.
                                </li>
                                <li>
                                    <strong>Datos Técnicos:</strong> Información del dispositivo, dirección IP, navegador, sistema operativo y datos de interacción en el sitio web.
                                </li>
                            </ul>
                        </section>

                        <section>
                            <h2>4. FINALIDADES DEL TRATAMIENTO</h2>
                            <ul>
                                <li>
                                    <strong>Prestación del Servicio:</strong> Facilitar y administrar el uso del sistema CRM para la gestión integral de datos e información.
                                </li>
                                <li>
                                    <strong>Gestión Comercial y Administrativa:</strong> Procesar y gestionar la relación contractual, facturación, atención al cliente y comunicaciones comerciales.
                                </li>
                                <li>
                                    <strong>Análisis y Mejora del Servicio:</strong> Realizar análisis estadísticos y de uso para optimizar la experiencia del usuario y mejorar nuestros servicios.
                                </li>
                                <li>
                                    <strong>Seguridad y Prevención:</strong> Implementar medidas de seguridad para proteger los datos contra accesos no autorizados, pérdidas o alteraciones.
                                </li>
                                <li>
                                    <strong>Cumplimiento Normativo:</strong> Asegurar que el tratamiento de datos se realice conforme a la normativa vigente en materia de protección de datos personales.
                                </li>
                            </ul>
                        </section>

                        <section>
                            <h2>5. BASE LEGAL PARA EL TRATAMIENTO</h2>
                            <ul>
                                <li>Consentimiento del Titular: Cuando se recogen datos directamente de los interesados.</li>
                                <li>Ejecución de un Contrato: Para la prestación y administración de los servicios contratados.</li>
                                <li>Cumplimiento de Obligaciones Legales: En cumplimiento de obligaciones establecidas por la ley.</li>
                                <li>Interés Legítimo: Cuando el tratamiento es necesario para fines propios de la gestión interna, siempre que no se vulneren los derechos fundamentales del titular.</li>
                            </ul>
                        </section>

                        <section>
                            <h2>6. COMPARTICIÓN DE DATOS</h2>
                            <p>
                                Los datos personales recopilados podrán compartirse con terceros en los siguientes casos:
                            </p>
                            <ul>
                                <li>
                                    <strong>Proveedores y Socios Tecnológicos:</strong> Únicamente para la prestación, administración y optimización del servicio (por ejemplo, servicios de hosting, análisis de datos y campañas publicitarias).
                                </li>
                                <li>
                                    <strong>Obligaciones Legales:</strong> En respuesta a requerimientos legales, judiciales o normativos.
                                </li>
                                <li>
                                    <strong>Transferencias Internacionales:</strong> Si fuese necesario, se realizarán con las garantías exigidas por la normativa ecuatoriana.
                                </li>
                            </ul>
                            <p>
                                <strong>Exoneración de Responsabilidad:</strong> En lo relativo a la compartición de datos, se deja expresamente que ALFA BUSINESS APP es únicamente el sistema CRM que utiliza la Empresa y queda eximido de cualquier responsabilidad derivada del uso, compartición o tratamiento de los datos proporcionados. La Empresa Usuaria asume de manera exclusiva la responsabilidad por la gestión, veracidad y seguridad de sus datos.
                            </p>
                        </section>

                        <section>
                            <h2>7. USO DE COOKIES Y TECNOLOGÍAS SIMILARES</h2>
                            <ul>
                                <li>Facilitar la navegación y personalizar el contenido.</li>
                                <li>Recopilar datos estadísticos que nos permitan mejorar el servicio.</li>
                                <li>Optimizar la gestión de campañas publicitarias mediante herramientas como Google Analytics, Meta Pixel y API de conversiones.</li>
                            </ul>
                            <p>
                                Los usuarios pueden gestionar sus preferencias de cookies a través de la configuración de su navegador.
                            </p>
                        </section>

                        <section>
                            <h2>8. MEDIDAS DE SEGURIDAD</h2>
                            <ul>
                                <li>
                                    <strong>Encriptación y Protocolos de Seguridad:</strong> Para asegurar la transmisión y almacenamiento de la información.
                                </li>
                                <li>
                                    <strong>Control de Acceso:</strong> Limitación de acceso a la información únicamente al personal autorizado.
                                </li>
                                <li>
                                    <strong>Auditorías y Monitoreo:</strong> Revisión periódica de los sistemas y procedimientos para detectar y prevenir vulnerabilidades.
                                </li>
                            </ul>
                        </section>

                        <section>
                            <h2>9. CONSERVACIÓN DE LOS DATOS</h2>
                            <p>
                                Los datos personales serán conservados durante el tiempo necesario para cumplir con las finalidades para las que fueron recopilados y para atender obligaciones legales o contractuales. Una vez cumplida la finalidad, se procederá a su eliminación, bloqueo o anonimización, de acuerdo con la normativa vigente.
                            </p>
                        </section>

                        <section>
                            <h2>10. DERECHOS DE LOS TITULARES</h2>
                            <ul>
                                <li>Acceso: Derecho a conocer qué datos se han recogido.</li>
                                <li>Rectificación: Derecho a corregir datos inexactos o incompletos.</li>
                                <li>Cancelación (Eliminación): Derecho a solicitar la eliminación de sus datos cuando estos ya no sean necesarios.</li>
                                <li>Oposición: Derecho a oponerse al tratamiento de sus datos.</li>
                                <li>Portabilidad: Derecho a recibir sus datos en un formato estructurado y transferible.</li>
                                <li>Revocatoria del Consentimiento: Derecho a retirar el consentimiento otorgado para el tratamiento de sus datos en cualquier momento.</li>
                            </ul>
                            <p>
                                Para ejercer estos derechos, los interesados pueden comunicarse a través de los canales establecidos en la sección de Contacto.
                            </p>
                        </section>

                        <section>
                            <h2>11. MODIFICACIONES A LA POLÍTICA DE PRIVACIDAD</h2>
                            <p>
                                La Empresa se reserva el derecho de modificar esta Política de Privacidad en cualquier momento. Cualquier cambio se publicará en el sitio web y, en casos significativos, se notificará a los usuarios mediante los canales de comunicación oficiales. Se recomienda revisar esta política de manera periódica para estar informado sobre posibles actualizaciones.
                            </p>
                        </section>

                        <section>
                            <h2>12. CONTACTO</h2>
                            <ul>
                                <li><strong>Correo electrónico:</strong> {{ $data['contact_email'] }}</li>
                                <li><strong>Teléfono:</strong> {{ $data['business_phone'] }}</li>
                                <li>
                                    <strong>Dirección:</strong> 
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
