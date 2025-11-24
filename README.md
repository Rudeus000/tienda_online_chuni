# 🛒 Tienda Online - E-commerce con PHP y Supabase

Proyecto de tienda en línea desarrollado en PHP, utilizando Supabase como backend y soporte para múltiples métodos de pago (PayPal, Mercado Pago, Yape).

## 📋 Características

- ✅ **Panel de administración completo**
  - Gestión de productos, categorías, usuarios y compras
  - Reportes de compras en PDF
  - Configuración del sistema

- ✅ **Sistema de usuarios**
  - Registro y autenticación
  - Recuperación de contraseña por email
  - Activación automática de cuentas

- ✅ **Métodos de pago**
  - PayPal
  - Mercado Pago
  - Yape (pago manual con confirmación)

- ✅ **Carrito de compras**
  - Agregar/eliminar productos
  - Actualización en tiempo real
  - Checkout seguro

- ✅ **Gestión de productos**
  - Múltiples imágenes por producto
  - Descripciones en HTML (CKEditor)
  - Categorización

## 🔧 Requerimientos

- **PHP 7.4 o superior** (recomendado 8.0+)
- **Composer** para gestionar dependencias
- **Cuenta de Supabase** (gratuita)
- **Servidor web** (Apache/Nginx) o servidor PHP built-in para desarrollo
- **Cuentas de pago** (opcional):
  - PayPal Business
  - Mercado Pago
  - Cuenta de correo electrónico para SMTP

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/tienda_online.git
cd tienda_online
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Configurar variables de entorno

Copia el archivo `.env.example` y crea un archivo `.env`:

```bash
# En Windows (PowerShell)
Copy-Item .env.example .env

# En Linux/Mac
cp .env.example .env
```

Edita el archivo `.env` y configura tus credenciales de Supabase:

```env
SUPABASE_URL=https://tu-proyecto-id.supabase.co
SUPABASE_ANON_KEY=tu_clave_anon_publica_aqui
SUPABASE_KEY=tu_clave_service_role_opcional
SUPABASE_SERVICE_ROLE=tu_service_role_key_opcional

SITE_URL=http://localhost:8000
KEY_CIFRADO=tu_clave_secreta_aqui
METODO_CIFRADO=aes-128-cbc
```

**⚠️ Importante:** Obtén tus credenciales de Supabase desde:
https://app.supabase.com/project/[tu-proyecto]/settings/api

### 4. Configurar Supabase

1. Crea un proyecto en [Supabase](https://supabase.com)
2. Ejecuta el script SQL para crear las tablas necesarias (ver `database/schema.sql` si está disponible)
3. O configura manualmente las tablas:
   - `admin`
   - `categorias`
   - `productos`
   - `usuarios`
   - `clientes`
   - `compras`
   - `detalle_compra`
   - `configuracion`
   - Y otras según tu esquema

### 5. Configurar el panel de administración

1. Accede a la tabla `admin` en Supabase y crea tu primer usuario administrador
2. O usa el script SQL para insertar un admin por defecto

### 6. Ejecutar el servidor

#### Desarrollo (Servidor PHP built-in):

```bash
php -S localhost:8000
```

Luego abre en el navegador:
- **Tienda:** http://localhost:8000/
- **Admin:** http://localhost:8000/admin/

#### Producción (Apache/Nginx):

Copia el proyecto al directorio del servidor web:
- XAMPP: `C:\xampp\htdocs\tienda_online`
- WAMP: `C:\wamp64\www\tienda_online`
- Linux: `/var/www/html/tienda_online`

## 📁 Estructura del Proyecto

```
tienda_online/
├── admin/                 # Panel de administración
│   ├── categorias/        # Gestión de categorías
│   ├── compras/           # Gestión de compras
│   ├── configuracion/     # Configuración del sistema
│   ├── productos/         # Gestión de productos
│   └── usuarios/          # Gestión de usuarios
├── clases/                # Clases PHP (funciones auxiliares)
├── config/                # Archivos de configuración
├── images/                # Imágenes del sitio
│   └── productos/         # Imágenes de productos
├── src/                   # Código fuente
│   ├── Database.php       # Clase para Supabase
│   └── StorageManager.php # Gestión de almacenamiento
├── vendor/                # Dependencias de Composer
├── .env                   # Variables de entorno (no subir a Git)
├── .env.example           # Plantilla de variables de entorno
├── composer.json          # Dependencias de PHP
└── README.md              # Este archivo
```

## 🔐 Configuración Adicional

### Configurar PayPal

1. Obtén tu `CLIENT_ID` de PayPal desde [PayPal Developer](https://developer.paypal.com)
2. Ve al panel de administración → Configuración
3. Ingresa tu `CLIENT_ID` y selecciona la moneda (USD, EUR, PEN, etc.)

### Configurar Mercado Pago

1. Obtén tu `TOKEN` y `PUBLIC_KEY` desde [Mercado Pago Developers](https://www.mercadopago.com.pe/developers)
2. Ve al panel de administración → Configuración
3. Ingresa tus credenciales de Mercado Pago

### Configurar Email (SMTP)

1. Ve al panel de administración → Configuración
2. Ingresa los datos de tu servidor SMTP:
   - Host SMTP
   - Puerto (587 para STARTTLS, 465 para SMTPS)
   - Email
   - Contraseña (o contraseña de aplicación)

### Configurar Yape

1. Sube tu código QR de Yape a `images/yape/yape_qr.png`
2. Configura el número de WhatsApp en `pago.php` para envío de comprobantes

## 📚 Tecnologías Utilizadas

- **Backend:**
  - PHP 7.4+
  - Supabase (PostgreSQL como servicio)
  - PHPMailer (envío de emails)
  - FPDF (generación de PDFs)

- **Frontend:**
  - Bootstrap 5.1.3
  - Font Awesome 5.15.4
  - CKEditor 5
  - Chart.js 4.4.2

- **APIs de Pago:**
  - PayPal SDK
  - Mercado Pago SDK v2.6.2

## 🛠️ Dependencias Principales

```json
{
  "mercadopago/dx-php": "2.6.2",
  "supabase/supabase-php": "^0.0.3",
  "vlucas/phpdotenv": "^5.5",
  "guzzlehttp/guzzle": "^7.0"
}
```

Ver `composer.json` para la lista completa.

## 📝 Notas de Desarrollo

- El proyecto usa **Supabase** como backend, no MySQL tradicional
- Las sesiones se gestionan con PHP nativo
- Las imágenes de productos se almacenan localmente (puede configurarse para Supabase Storage)
- El sistema maneja automáticamente la zona horaria `America/Lima`
- Todos los datos sensibles se cifran usando AES-128-CBC

## 🔒 Seguridad

- ✅ Variables de entorno en `.env` (no subir a Git)
- ✅ Cifrado de datos sensibles
- ✅ Validación de sesiones en todas las páginas protegidas
- ✅ Protección contra XSS con `htmlspecialchars()`
- ✅ Manejo seguro de contraseñas con `password_hash()`

## 📖 Documentación Adicional

- [INSTRUCCIONES.md](INSTRUCCIONES.md) - Guía de uso y configuración
- [DEPLOYMENT.md](DEPLOYMENT.md) - Guía de despliegue en producción

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 👨‍💻 Autores

- **Marco Robles** - *Desarrollo original* - [mroblesdev](https://github.com/mroblesdev)
- Ver vídeo del desarrollo de este proyecto [playlist](https://www.youtube.com/playlist?list=PL-Mlm_HYjCo-Odv5-wo3CCJ4nv0fNyl9b)

## 🙏 Agradecimientos

- A todos los contribuidores del proyecto
- A la comunidad de Supabase por su excelente documentación

---

⭐ Si este proyecto te fue útil, ¡dale una estrella en GitHub!
