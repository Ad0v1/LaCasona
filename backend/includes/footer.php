<?php
// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<footer id="footer" class="s-footer">
    <div class="row s-footer__main-content">
        <div class="column xl-6 md-12 s-footer__block s-footer__about">
            <div class="s-footer__logo">
                <a class="logo" href="Index.html">
                    <img src="assets/imagenes/LOGO/LOGO.jpg" alt="Página de inicio">
                </a>
            </div>

            <p>La Casona es el lugar perfecto para disfrutar de platos deliciosos en un ambiente acogedor. Te esperamos con la mejor atención y sabores únicos.</p>
        </div>

        <div class="column xl-6 md-12 s-footer__block s-footer__info">
            <div class="row">
                <div class="column xl-6 lg-12">
                    <h5>Ubicación</h5>
                    <p>
                    Antigua panamericana, <br>
                    Club Kawai Unión Biblíca del Perú - km 88.8
                    </p>
                </div>
                <div class="column xl-6 lg-12">
                    <h5>Contactos</h5>
                    <ul class="link-list">
                        <li><a href="mailto:casonaKawai@gmail.com">casonaKawai@gmail.com</a></li>
                        <li><a href="tel:+51980436234">+51 980 436 234</a></li>
                    </ul>
                </div>
                <div class="column">
                    <h5>Horarios de Atención</h5>
                    <ul class="opening-hours">
                        <li><span class="opening-hours__days">De lunes a domingo</span><span class="opening-hours__time"> 7:00am - 12:00am</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row s-footer__bottom">
        <div class="column xl-6 lg-12">
            <ul class="s-footer__social social-list">
                <li>
                    <a href="https://www.facebook.com/unionbiblicadelperu" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill:rgba(0, 0, 0, 1);">
                            <path d="M20,3H4C3.447,3,3,3.448,3,4v16c0,0.552,0.447,1,1,1h8.615v-6.96h-2.338v-2.725h2.338v-2c0-2.325,1.42-3.592,3.5-3.592 
                                c0.699-0.002,1.399,0.034,2.095,0.107v2.42h-1.435c-1.128,0-1.348,0.538-1.348,1.325v1.735h2.697l-0.35,2.725h-2.348V21H20 
                                c0.553,0,1-0.448,1-1V4C21,3.448,20.553,3,20,3z"></path></svg>
                        <span class="u-screen-reader-text">Facebook</span>
                    </a>
                </li>
                <li>
                    <a href="https://wa.me/+51980436234" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.51c-.292-.147-1.728-.853-1.995-.95-.266-.098-.46-.147-.654.147-.196.293-.75.95-.92 1.147-.17.196-.34.22-.632.073-.292-.146-1.233-.455-2.35-1.45-.867-.773-1.45-1.732-1.617-2.024-.17-.293-.017-.45.13-.598.133-.132.292-.34.437-.51.146-.17.196-.293.292-.487.097-.196.05-.366-.024-.51-.073-.146-.654-1.593-.896-2.182-.236-.568-.477-.49-.654-.5h-.555c-.196 0-.51.073-.776.366-.266.293-1.017.996-1.017 2.43s1.042 2.82 1.188 3.013c.147.195 2.04 3.115 4.946 4.243.69.298 1.227.475 1.646.608.692.22 1.323.189 1.82.114.555-.085 1.728-.707 1.97-1.39.243-.683.243-1.268.17-1.39-.073-.121-.266-.194-.555-.34z"/>
                            <path d="M12 2C6.485 2 2 6.485 2 12c0 1.85.503 3.68 1.457 5.265L2 22l4.956-1.243A9.947 9.947 0 0012 22c5.515 0 10-4.485 10-10S17.515 2 12 2zm0 18c-1.63 0-3.228-.433-4.606-1.252l-.331-.195-2.945.736.79-2.798-.212-.336A7.925 7.925 0 014 12c0-4.411 3.589-8 8-8s8 3.589 8 8-3.589 8-8 8z"/>
                        </svg>
                        <span class="u-screen-reader-text">WhatsApp</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="column xl-6 lg-12">
            <p class="ss-copyright">
                <span>© Copyright Kawaii 2025</span>
                <a href="#" onclick="abrirLogin()" style="margin-left: 15px; color: #ff9800; text-decoration: underline;">
                    Acceso Administrativo
                </a>
            </p>
        </div>
    </div>

    <div class="ss-go-top">
        <a class="smoothscroll" title="Volver arriba" href="#top">
            <svg clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fill-rule="nonzero"/></svg>
        </a>
        <span>Volver arriba</span>
    </div>
</footer>
