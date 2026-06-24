# In-Fin Pharmacy

Proyecto PHP + SQLite para la entrega de CRUD en Github Codespaces.

## Integrantes

- Tomas Teihuel
- Gerardo Ceron
- Joaquin Lespai

## Tema

Sistema web para gestionar el inventario de medicamentos de una farmacia. La aplicacion permite registrar usuarios, iniciar sesion, cerrar sesion, crear, leer, modificar y eliminar medicamentos usando una base de datos SQLite. Tambien registra un historial de cambios con usuario, fecha, accion y detalle.

## Archivos principales

- `index.php`: pagina principal, descripcion del proyecto y CRUD funcional.
- `schema.sql`: estructura de las tablas `medicamentos`, `usuarios` e `historial`.
- `mockup.png`: mockup de la interfaz principal.
- `logo-infin-pharmacy1.png`: logo reutilizado del proyecto anterior.
- `farmacia-fondo.png`: imagen de fondo reutilizada del proyecto anterior.
- `entrega.txt`: archivo para pegar el link del deploy de Github Codespaces.

## Ejecucion en Github Codespaces

Desde la carpeta del proyecto:

```bash
php -S 0.0.0.0:3000
```

Luego abrir el puerto `3000` como publico y copiar el enlace terminado en `.app.github.dev` dentro de `entrega.txt`.

## Acceso de prueba

- Correo: `admin@infin.cl`
- Contrasena: `admin123`

Tambien se puede crear una cuenta nueva desde la pantalla de acceso. El historial registra registro de usuario, inicio de sesion, cierre de sesion y cambios CRUD sobre medicamentos.
