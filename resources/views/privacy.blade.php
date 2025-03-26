@extends('layouts.app', ['class' => 'main-content-has-bg'])
@section('content')
@include('layouts.headers.guest')
<div class="container my-5 lw-terms-and-conditions-page">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0">
                <div class="card-header text-center bg-primary text-white">
                    <h1 class="mb-0">POLÍTICA DE PRIVACIDAD</h1>
                    <small>Última actualización: {{ $data['updated_at'] }}</small>
                </div>
                <div class="card-body px-lg-5 py-lg-5">
                    

                    <!-- Contenido -->
                    <div class="content">
                        <section id="introduccion" class="mb-4">
                            <h2>1. INTRODUCCIÓN</h2>
                            <p>
                                En {{ $data['vendor_title'] }}, nos comprometemos a proteger la privacidad y la seguridad de los datos personales que recopilamos y tratamos, garantizando el ejercicio del derecho a la protección de datos de conformidad con la Ley Orgánica de Protección de Datos Personales de Ecuador y su reglamento. Esta Política de Privacidad describe de manera clara y transparente cómo se recogen, utilizan, comparten y protegen los datos de carácter personal de nuestros clientes, proveedores y empleados, así como de la información corporativa que nos proporciona la Empresa Usuaria.
                            </p>
                        </section>
                        
                        <section id="responsable" class="mb-4">
                            <h2>2. RESPONSABLE DEL TRATAMIENTO</h2>
                            <p>
                                <strong>Nombre Legal:</strong> ALFA BUSINESS PLANET ALFA BP S.A.S<br>
                                <strong>Correo electrónico de contacto:</strong> info@alfabusiness.app<br>
                                <strong>Nota:</strong> ALFA BUSINESS APP es el sistema CRM que la Empresa utiliza para gestionar sus datos corporativos. La Empresa Usuaria es la única responsable del tratamiento y la veracidad de la información que introduce en el sistema.
                            </p>
                        </section>
                        
                        <section id="datos-personales" class="mb-4">
                            <h2>3. DATOS PERSONALES RECOPILADOS</h2>
                            <p>La información que recopilamos se clasifica en dos categorías:</p>
                            <div class="ml-4">
                                <h3>3.1. Datos de la Empresa</h3>
                                <ul>
                                    <li><strong>Datos de la Empresa:</strong> {{ $data['vendor_title'] }}.</li>
                                    <li><strong>Información Comercial:</strong> {{ $data['business_information'] }}.</li>
                                    <li>
                                        <strong>Dirección y Contacto:</strong> {{ $data['address_and_contact'] }}, {{ $data['address_line'] }}, {{ $data['postal_code'] }}, Quito, Ecuador.
                                    </li>
                                    <li><strong>Teléfono Comercial:</strong> +593 {{ $data['business_phone'] }}.</li>
                                    <li><strong>Correo Electrónico:</strong> {{ $data['contact_email'] }}.</li>
                                </ul>
                                <h3>3.2. Datos de Uso y Navegación</h3>
                                <p>
                                    Durante el uso de nuestro sitio web y servicios, recopilamos información adicional mediante:
                                </p>
                                <ul>
                                    <li><strong>Cookies y Tecnologías de Seguimiento:</strong> Utilizamos cookies propias y de terceros (como Google Analytics, Meta Pixel y API de conversiones) para mejorar la experiencia del usuario, analizar la interacción y optimizar campañas publicitarias.</li>
                                    <li><strong>Datos Técnicos:</strong> Información del dispositivo, dirección IP, navegador, sistema operativo y datos de interacción en el sitio web.</li>
                                </ul>
                            </div>
                        </section>
                        
                        <section id="finalidades" class="mb-4">
                            <h2>4. FINALIDADES DEL TRATAMIENTO</h2>
                            <p>
                                <strong>Prestación del Servicio:</strong> Facilitar y administrar el uso del sistema CRM para la gestión integral de datos e información;<br>
                                <strong>Gestión Comercial y Administrativa:</strong> Procesar y gestionar la relación contractual, facturación, atención al cliente y comunicaciones comerciales;<br>
                                <strong>Análisis y Mejora del Servicio:</strong> Realizar análisis estadísticos y de uso para optimizar la experiencia del usuario y mejorar nuestros servicios;<br>
                                <strong>Seguridad y Prevención:</strong> Implementar medidas de seguridad para proteger los datos contra accesos no autorizados, pérdidas o alteraciones;<br>
                                <strong>Cumplimiento Normativo:</strong> Asegurar que el tratamiento de datos se realice conforme a la normativa vigente en materia de protección de datos personales.
                            </p>
                        </section>
                        
                        <section id="base-legal" class="mb-4">
                            <h2>5. BASE LEGAL PARA EL TRATAMIENTO</h2>
                            <p>
                                El tratamiento de datos personales se realiza con base en: 
                                <strong>Consentimiento del Titular:</strong> Cuando se recogen datos directamente de los interesados;<br>
                                <strong>Ejecución de un Contrato:</strong> Para la prestación y administración de los servicios contratados;<br>
                                <strong>Cumplimiento de Obligaciones Legales:</strong> En cumplimiento de obligaciones establecidas por la ley;<br>
                                <strong>Interés Legítimo:</strong> Cuando el tratamiento es necesario para fines propios de la gestión interna, siempre que no se vulneren los derechos fundamentales del titular.
                            </p>
                        </section>
                        
                        <section id="comparticion" class="mb-4">
                            <h2>6. COMPARTICIÓN DE DATOS</h2>
                            <p>
                                Los datos personales recopilados podrán compartirse con terceros en los siguientes casos: 
                                <strong>Proveedores y Socios Tecnológicos:</strong> Únicamente para la prestación, administración y optimización del servicio (por ejemplo, servicios de hosting, análisis de datos y campañas publicitarias);<br>
                                <strong>Obligaciones Legales:</strong> En respuesta a requerimientos legales, judiciales o normativos;<br>
                                <strong>Transferencias Internacionales:</strong> Si fuese necesario, se realizarán con las garantías exigidas por la normativa ecuatoriana.
                            </p>
                            <p>
                                <strong>Exoneración de Responsabilidad:</strong> ALFA BUSINESS APP es únicamente el sistema CRM que utiliza la Empresa y queda eximido de cualquier responsabilidad derivada del uso, compartición o tratamiento de los datos proporcionados. La Empresa Usuaria asume la responsabilidad por la gestión, veracidad y seguridad de sus datos.
                            </p>
                        </section>
                        
                        <section id="cookies" class="mb-4">
                            <h2>7. USO DE COOKIES Y TECNOLOGÍAS SIMILARES</h2>
                            <p>
                                Utilizamos cookies y tecnologías de seguimiento para facilitar la navegación, personalizar el contenido, recopilar datos estadísticos que nos permitan mejorar el servicio y optimizar campañas publicitarias. Los usuarios pueden gestionar sus preferencias a través de la configuración de su navegador.
                            </p>
                        </section>
                        
                        <section id="seguridad" class="mb-4">
                            <h2>8. MEDIDAS DE SEGURIDAD</h2>
                            <p>
                                Implementamos medidas técnicas, organizativas y jurídicas para garantizar la protección de los datos personales, tales como: 
                                <strong>Encriptación y Protocolos de Seguridad:</strong> Para asegurar la transmisión y almacenamiento de la información;<br>
                                <strong>Control de Acceso:</strong> Limitación de acceso a la información únicamente al personal autorizado;<br>
                                <strong>Auditorías y Monitoreo:</strong> Revisión periódica de sistemas y procedimientos para detectar y prevenir vulnerabilidades.
                            </p>
                        </section>
                        
                        <section id="conservacion" class="mb-4">
                            <h2>9. CONSERVACIÓN DE LOS DATOS</h2>
                            <p>
                                Los datos personales serán conservados durante el tiempo necesario para cumplir con las finalidades para las que fueron recopilados y atender obligaciones legales o contractuales. Una vez cumplida la finalidad, se procederá a su eliminación, bloqueo o anonimización, conforme a la normativa vigente.
                            </p>
                        </section>
                        
                        <section id="derechos" class="mb-4">
                            <h2>10. DERECHOS DE LOS TITULARES</h2>
                            <p>
                                Los titulares de los datos personales tienen los siguientes derechos: acceso, rectificación, cancelación, oposición, portabilidad y revocatoria del consentimiento. Para ejercer estos derechos, comuníquese a través de los canales establecidos en la sección de Contacto.
                            </p>
                        </section>
                        
                        <section id="modificaciones" class="mb-4">
                            <h2>11. MODIFICACIONES A LA POLÍTICA DE PRIVACIDAD</h2>
                            <p>
                                La Empresa se reserva el derecho de modificar esta Política de Privacidad en cualquier momento. Cualquier cambio se publicará en el sitio web y, en casos significativos, se notificará a los usuarios mediante los canales oficiales. Se recomienda revisar esta política periódicamente.
                            </p>
                        </section>
                        
                        <section id="contacto" class="mb-4">
                            <h2>12. CONTACTO</h2>
                            <ul class="list-unstyled ml-4">
                                <li><strong>Correo electrónico:</strong> {{ $data['contact_email'] }}</li>
                                <li><strong>Teléfono:</strong> +593 {{ $data['business_phone'] }}</li>
                                <li><strong>Dirección:</strong> {{ $data['address_and_contact'] }}, {{ $data['address_line'] }}, Quito, Ecuador</li>
                            </ul>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
