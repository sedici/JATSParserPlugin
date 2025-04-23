# JATSParserPlugin Documentación

Este plugin extiende el [plugin original jatsParser](https://github.com/Vitaliy-1) y permite generar PDFs a partir de archivos XML con estándar JATS en OJS 3.3. Incorpora mejoras en la generación de PDFs, soporte multilenguaje, plantillas personalizadas, y un sistema de tablas de citas según el estilo de citación.

## 📦 Instalación

👉 1. Navega hasta la carpeta `plugins/generic` comenzando desde la raíz de OJS:
```bash
cd plugins/generic
```

👉 2. Clona el repositorio con el comando:
```bash
git clone --recursive https://github.com/sedici/JATSParserPlugin.git jatsParser
```

👉 3. Instalar las dependencias necesarias para la conversión de JATS a PDF: entra en la carpeta JATSParser con:
```bash
cd JATSParser
```
y dentro ejecuta el comando:
```bash
composer install
```

👉 4. Instalar las fuentes necesarias para generar el PDF: Ejecutar el script install-fonts.php.
Para ello, dentro de la carpeta JATSParser diríjase a la carpeta install-fonts con:
```bash
cd scripts/install-fonts
```
Luego en la consola ejecute el siguiente comando:
```bash
php install-fonts.php
```

## ⚙️ Funcionalidades y cambios principales

- Conversión de XML JATS a HTML, luego a PDF mediante TCPDF.
- Plantillas personalizadas para la estructura del PDF (cada una con sus componentes: Header, TemplateBody, Footer, Body).
- ***Impresión*** de metadatos en el PDF (como títulos, resúmenes, palabras clave, fechas, etc) ***en diferentes idiomas*** (actualmente solo se soporta español, inglés y portugués) 
- Interfaz visual en OJS para cargar citas según el estilo de citación y contexto del artículo: la Tabla de Citas.
- Separación de responsabilidades (metadatos, plantilla, renderers).
- Soporte multilenguaje textos específicos (como por ejemplo: Palabras clave - Keywords - Palavras chave) del PDF (gracias a la clase Translations)
- Estructura modular con ***Strategy Pattern*** para facilitar nuevas plantillas sin modificar la lógica central.
- Renderers reutilizables para imprimir bloques o elementos específicos en cualquier parte del PDF.
- ***Compatibilidad*** con el plugin ***Texture*** y adecuación para estilos como APA e IEEE.

## 🧱 Estructura del Plugin

```mathematica
jatsParser/
├── JATSParser/
│   ├── src/
│   │   └── JATSParser/
│   │       ├── Back/
│   │       ├── Body/
│   │       ├── HTML/
│   │       └── PDF/
│   │           ├── PDFBodyHelper.php                   # Modificado para procesar el contenido del XML JATS correspondiente al artículo al de generar el PDF
│   │           ├── TemplateStrategy.php                # Implementado para manejar plantillas dinámicamente (Strategy Pattern)
│   │           ├── PDFConfig/                          # Configuración agregada
│   │           │   ├── Configuration.php               # Agregado para centralizar metadatos y estilos del PDF.
│   │           │   └── Translations.php                # Agregado para definir traducciones de textos específicos usados al generar el PDF
│   │           └── Templates/                    
│   │               ├── Renderers/
│   │               │   ├── GroupRenderer/
│   │               │   └── SingleRenderer/
│   │               ├── BaseTemplate.php
│   │               ├── GenericComponent.php
│   │               ├── GenericTemplate.php
│   │               └── TemplateOne/
│   │                   ├── TemplateOne.php
│   │                   └── Components/
│   │                       ├── Body.php
│   │                       ├── Footer.php
│   │                       ├── Header.php
│   │                       └── TemplateBody.php
│   ├── scripts/
│   │   └── install-fonts/
│   │       └── install-fonts.php
│   ├── vendor/
│   ├── logo/
│   ├── examples/
│   └── composer.json
│
├── app/
├── images/
├── locale/
├── resources/
├── templates/
├── classes/
│   ├── components/
│   │   └── forms/
│   │       ├── CitationStyles/
│   │       │   ├── Stylesheets/
│   │       │   │   ├── ApaStylesheet.php
│   │       │   │   └── GenericStylesheet.php
│   │       │   ├── ApaCitationTable.php
│   │       │   └── GenericCitationTable.php
│   │       ├── Helpers/
│   │       │   └── process_citations.php
│   │       ├── TableHTML.php
│   │       └── PublicationJATSUploadForm.inc.php
│   ├── daos/
│   │   └── CustomPublicationSettingsDAO.inc.php
│   └── JATSParserDocument.inc.php
│
└── **archivos específicos del plugin**
```

📁 Archivos y directorios clave:
- `JatsParserPlugin.php`: Archivo principal, define flujo y hooks.
- `PDF/Templates/`: Contiene las plantillas de PDF.
- `PDF/Templates/Renderers/`: Renderers reutilizables para elementos del PDF.
- `PDFConfig/`: Configuración, estilos y traducciones.
- `forms/CitationStyles/`: Contiene las clases específicas de estilos de citación (como ApaCitationTable.php) y sus estilos correspondientes en `Stylesheets/`.
- `forms/Helpers/process.citations.php`: Se encarga de procesar y analizar las citas recibidas desde la Tabla de Citas para posteriormente llamar a CustomPublicationSettingsDao.
- `daos/CustomPublicationSettingsDAO.php`: Accede y actualiza la configuración de citas en la base de datos, tanto para lectura como para escritura. Al generar el PDF se obtienen la configuración. Al guardar las citas desde la tabla, se actualiza la configuración.
- `forms/TableHTML.php`: Procesa el XML JATS del artículo para generar el contenido de la Tabla de Citas (contexto, referencias, estilo).
