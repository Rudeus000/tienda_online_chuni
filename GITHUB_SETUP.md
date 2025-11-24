# 📤 Guía para Subir el Proyecto a GitHub

## Paso 1: Verificar archivos que se subirán

```bash
git status
```

Esto mostrará todos los archivos que se agregarán al repositorio. Verifica que NO aparezca el archivo `.env` (debe estar en .gitignore).

## Paso 2: Agregar archivos al repositorio

```bash
# Agregar todos los archivos (respetando .gitignore)
git add .

# Ver qué archivos se agregaron
git status
```

## Paso 3: Hacer el primer commit

```bash
git commit -m "Initial commit: Tienda Online con PHP y Supabase"
```

O si prefieres un mensaje más descriptivo:

```bash
git commit -m "Initial commit: E-commerce completo con PHP, Supabase, PayPal, Mercado Pago y Yape"
```

## Paso 4: Crear repositorio en GitHub

1. Ve a https://github.com y crea una nueva cuenta o inicia sesión
2. Haz clic en el botón "+" (arriba a la derecha) y selecciona "New repository"
3. Nombre del repositorio: `tienda_online` (o el que prefieras)
4. Descripción: "Tienda en línea desarrollada con PHP y Supabase"
5. **NO marques** "Initialize with README" (ya tenemos uno)
6. Elige si será público o privado
7. Haz clic en "Create repository"

## Paso 5: Conectar el repositorio local con GitHub

Después de crear el repositorio en GitHub, copia la URL que te muestra (algo como: `https://github.com/tu-usuario/tienda_online.git`)

Luego ejecuta:

```bash
# Reemplaza la URL con tu repositorio real
git remote add origin https://github.com/tu-usuario/tienda_online.git

# Verificar que se agregó correctamente
git remote -v
```

## Paso 6: Subir el código a GitHub

```bash
# Cambiar a la rama main (si estás en otra)
git branch -M main

# Subir el código
git push -u origin main
```

## ⚠️ IMPORTANTE: Antes de hacer push

### Verifica que estos archivos NO se suban:

- `.env` (debe estar en .gitignore)
- `vendor/` (debe estar en .gitignore)
- Archivos de prueba (`test_*.php`, `debug_*.php`)
- Logs (`*.log`)

### Para verificar qué archivos se subirán:

```bash
git status
```

Si ves algún archivo sensible que NO debería subirse:

```bash
# Remover del staging
git reset HEAD nombre-del-archivo

# Asegurarte de que esté en .gitignore
# Luego agregar de nuevo
git add .
```

## 🔐 Configuración Adicional

### Configurar tu identidad en Git (si no lo has hecho):

```bash
git config --global user.name "Tu Nombre"
git config --global user.email "tu-email@ejemplo.com"
```

### Si usas autenticación SSH:

1. Genera una clave SSH:
```bash
ssh-keygen -t ed25519 -C "tu-email@ejemplo.com"
```

2. Agrega la clave a GitHub:
   - Copia el contenido de `~/.ssh/id_ed25519.pub`
   - Ve a GitHub → Settings → SSH and GPG keys → New SSH key
   - Pega la clave y guarda

3. Usa la URL SSH en lugar de HTTPS:
```bash
git remote set-url origin git@github.com:tu-usuario/tienda_online.git
```

## 📝 Comandos Útiles para Futuros Cambios

### Actualizar el repositorio después de hacer cambios:

```bash
# Ver qué archivos cambiaron
git status

# Agregar archivos modificados
git add .

# Hacer commit
git commit -m "Descripción de los cambios"

# Subir cambios
git push
```

### Crear una nueva rama para una funcionalidad:

```bash
git checkout -b nombre-de-la-funcionalidad
# Hacer cambios...
git add .
git commit -m "Agregar nueva funcionalidad"
git push -u origin nombre-de-la-funcionalidad
```

### Ver el historial de commits:

```bash
git log --oneline
```

## 🎉 ¡Listo!

Una vez completados estos pasos, tu proyecto estará disponible en GitHub y podrás compartirlo con otros desarrolladores o desplegarlo en servicios de hosting que se integren con GitHub (como Vercel, Netlify, Railway, etc.).


