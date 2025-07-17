<p align="center">
  <a href="" rel="noopener">
 <img width=200px height=200px src="https://i.imgur.com/6wj0hh6.jpg" alt="Project logo"></a>
</p>

<h3 align="center">undefined</h3>

<div align="center">

[![Status](https://img.shields.io/badge/status-active-success.svg)]()
[![GitHub Issues](https://img.shields.io/github/issues/kylelobo/The-Documentation-Compendium.svg)](https://github.com/kylelobo/The-Documentation-Compendium/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/kylelobo/The-Documentation-Compendium.svg)](https://github.com/kylelobo/The-Documentation-Compendium/pulls)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](/LICENSE)

</div>

---

<p align="center"> Aplicacion simple para gestionar la reserva de un SUM
    <br> 
</p>

## 📝 Table of Contents

- [Acerca](#about)
- [Comenzando](#getting_started)
- [Software usado](#built_using)
- [Authors](#authors)

## 🧐 Acerca <a name = "about"></a>

Esta aplicación de desarrolló para la gestion y uso del SUM de la biblioteca de la FADU-UBA.

## 🏁 Comenzando <a name = "getting_started"></a>

Solo requiere hacer una copia del repositorio en la maquina local.

### Pre-requisitos
Tener instalado un servidor web , php e instalar via composer las librerias phpmailer , boostrap y fullcalendar

### Installing

Solo tenes que poner todo en un directorio accesible por tu servidor web y listo.
Tenes que crear ademas un archivo de configuracion llamado config.php que sea mas o menos asi :

```php
// Configuración de la base de datos
define('DB_HOST', 'localhost'); // db si es un contenedor Docker
define('DB_NAME', 'nombre_base');
define('DB_USER', 'user_base');
define('DB_PASS', 'password_base');

// Configuración de la zona horaria
define('TIMEZONE', 'America/Argentina/Buenos_Aires');

// Configuración de restricciones de reservas
define('HORA_INICIO_PERMITIDA', '09:00:00');
define('HORA_FIN_PERMITIDA', '21:00:00');
define('DIAS_PERMITIDOS', [1, 2, 3, 4, 5]); // Lunes a viernes
define('ANTICIPACION_HORAS', 36); // Anticipación mínima en horas

// Configuración de directorio de uploads
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB

// Configuración de PHPMailer (SMTP)
define('SMTP_HOST', 'smtp.host_a_user');
define('SMTP_USERNAME', 'usuario_smtp');
define('SMTP_PASSWORD', 'contraseña_smtp');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_FROM_EMAIL', 'usuario@que_envia_mail');
define('SMTP_FROM_NAME', 'Sistema de Reservas');
define('ENCARGADO_EMAIL', 'usuario@que_envia_mail');
define('ENCARGADO_NAME', 'Encargado de Aula');

// Otras configuraciones
define('MAX_ASISTENTES', 50);
define('TELEFONO_REGEX', '/^[0-9\s\+\(\)\-]{7,20}$/');

// Credenciales del Encargado
define('ENCARGADO_USER', 'admin');
// Para generar el hash, ejecuta una vez: echo password_hash('tu_contraseña_segura', PASSWORD_DEFAULT);
// Y pega el resultado aquí.
define('ENCARGADO_PASS_HASH', 'hash de la contraseña');

/**
 * Switch general del sistema de reservas.
 * true: El sistema funciona normalmente.
 * false: Se deshabilita la creación de nuevas reservas (modo vacaciones/mantenimiento).
 */
define('SISTEMA_HABILITADO', true);
```

## ⛏️ Software Usado <a name = "built_using"></a>

- [MariaDb] - Database
- [PHP - HTML] - Server Framework
- [CSS] - Web Framework

## ✍️ Autor <a name = "authors"></a>

- [@LopezAlejandro](https://github.com/LopezAlejandro) - Idea & Trabajo inicial

