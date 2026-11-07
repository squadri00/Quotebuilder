# Custom code before </head> and </body>

Super Admin, Platform Settings, "Custom code" card.

- **Before the closing head tag** and **before the closing body tag**: two boxes. What you type is added exactly as typed (not escaped), just before `</head>` and just before `</body>`.
- **Add the code to**: Public website pages, Sign-in/sign-up pages, Business dashboard, Super Admin area. Until it is saved once, the default is public pages and sign-in pages. All boxes unticked means nowhere.
- **Custom code is on**: untick to switch it off everywhere without deleting the text.
- Code that contains the `html`, `head` or `body` tags themselves is refused (it would break every page); up to 20,000 characters per box.
- Only a Super Admin can change it, so only paste code you trust. Every change is written to the audit log.
- Saving another part of Platform Settings never changes the custom code (its own hidden marker `custom_code_form` decides).
- Where it lives: `platform_settings.head_code`, `body_code`, `custom_code_enabled`, `custom_code_areas`; the four layouts include `partials/custom-code` at both spots; `PlatformSetting::customCode($where, $area)` decides what is printed. Tests: `tests/Feature/CustomCodeTest.php`.
