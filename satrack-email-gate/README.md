# Satrack Email Gate

Satrack Email Gate es un plugin de WordPress que restringe el acceso a secciones del sitio solicitando un correo previamente autorizado y la resolución de un captcha aritmético sencillo.

## Funcionalidad

- Muestra un formulario mediante el shortcode `email_gate_form`.
- Solicita un correo electrónico y un captcha con operaciones básicas (`+`, `-`, `x`).
- Envía los datos al endpoint REST `email-gate-pro/v1/verify` para validar el acceso.
- Utiliza un captcha cuyo resultado siempre es un número entre `0` y `40`. Si se genera un resultado negativo o mayor a `40` se eligen nuevos números u operación.

## Estructura

```
satrack-email-gate/
├── assets/               Recursos estáticos como JavaScript.
├── src/                  Código fuente del plugin.
│   ├── Application/      Casos de uso de la aplicación.
│   ├── Infrastructure/   Integraciones con WordPress (REST y shortcodes).
│   └── Support/          Servicios auxiliares como logging y configuración.
├── satrack-email-gate.php  Archivo principal del plugin.
└── uninstall.php         Lógica de desinstalación.
```

## Especificaciones técnicas

- Desarrollado en PHP y pensado para WordPress 5.0 o superior.
- Interfaz de verificación expuesta mediante la API REST de WordPress.
- El formulario front-end utiliza `fetch` y un script JavaScript mínimo incluido en `assets/js/form.js`.
- Dependencias internas organizadas en `src/` siguiendo el patrón de namespaces de PHP.
- Captcha aritmético con resultado restringido a un valor positivo menor o igual a `40`.
