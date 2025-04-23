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
├── JATSParser/                                         # Biblioteca core para procesamiento de documentos JATS
│   ├── src/
│   │   └── JATSParser/
│   │       ├── Back/
│   │       ├── Body/
│   │       ├── HTML/
│   │       └── PDF/
│   │           ├── PDFBodyHelper.php                   # Se ha añadido la funcionalidad de procesar el contenido XML JATS para estructurar de forma correcta el cuerpo del PDF si el artículo está en APA 
│   │           ├── TemplateStrategy.php                # Se ha implementado para manejar plantillas dinámicamente implementando un Strategy Pattern
│   │           ├── PDFConfig/                          # Se ha definido una carpeta para almacenar la configuración centralizada para la generación de PDFs
│   │           │   ├── Configuration.php               # Se ha implementado para centralizar metadatos y estilos 
│   │           │   └── Translations.php                # Se ha implementado para almacenar traducciones para textos específicos en PDFs generados 
│   │           └── Templates/                          # Se ha definido un sistema de plantillas modulares y extensibles
│   │               ├── Renderers/                      # Se ha implementado un sistema de renderizado reutilizable con separación de responsabilidades
│   │               │   ├── GroupRenderer/              # Se implementaron renderizadores para elementos compuestos (resúmenes en diferentes idiomas, información completa de autores, etc) 
│   │               │   └── SingleRenderer/             # Se implementaron renderizadores para elementos atómicos (textos, imágenes, licencia, etc)
│   │               ├── BaseTemplate.php                # Se ha implementado como clase base abstracta con métodos comunes para todas las plantillas. Reconoce los componentes de cada plantilla.
│   │               ├── GenericTemplate.php             # Se ha implementado como clase base que inicializa los componentes correspondientes a la plantilla reconocida por BaseTemplate.
│   │               ├── GenericComponent.php            # Se ha implementado como clase base para todos los componentes con funcionalidad compartida
│   │               └── TemplateOne/                    # Se ha implementado una plantilla personalizada llamada "TemplateOne"
│   │                   ├── TemplateOne.php             # Se ha implementado la clase principal de la plantilla. Carga sus componentes correspondientes que luego serán procesados en BaseTemplate.php
│   │                   └── Components/                 # Componentes específicos de esta plantilla
│   │                       ├── Body.php                # Renderiza el contenido del XML JATS del artículo (incluidas las referencias bibliograficas) 
│   │                       ├── Footer.php              # Renderiza el pie de pagina con la informacion de la licencia llamando al Renderer individual "Licence" 
│   │                       ├── Header.php              # Renderiza el encabezado llamando a Renderers especificos segun los elementos que se deseen imprimir
│   │                       └── TemplateBody.php        # Renderiza la caratula del artículo con datos introductorios, utilizando Renderers especificos según los elementos que se deseen imprimir
│   ├── scripts/                                        # Se ha añadido esta carpeta que contiene los Scripts necesarios para el funcionamiento del plugin
│   │   └── install-fonts/                              
│   │       └── install-fonts.php                       # Script de instalacion automatica de fuentes personalizadas para TCPDF
│   ├── vendor/
│   ├── logo/                                           # Se han añadido logos que son utilizados para la generación del PDF, tales como como el logo ORCID o los logos correspondientes a los tipos de licencias Creative Commons                                                 
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
│   │   └── forms/                                      # Formularios y elementos relacionados a UI
│   │       ├── CitationStyles/                         # Se ha implementado una Tabla de Citas para APA 7 
│   │       │   ├── Stylesheets/                        # Se ha creado una carpeta que almacena los estilos para cada formato de citación (actualmente solo se soporta APA)
│   │       │   │   ├── ApaStylesheet.php               # Estilos específicos para formato APA 
│   │       │   │   └── GenericStylesheet.php           # Clase base abstracta con estilos comunes
│   │       │   ├── ApaCitationTable.php                # Implementación de una tabla de citas con estilo de citación APA
│   │       │   └── GenericCitationTable.php            # Se ha implementado una clase base con un patrón Template Method para tablas de citación
│   │       ├── Helpers/                                # Se ha agregado una carpeta con funciones auxiliares para el procesamiento de formularios
│   │       │   └── process_citations.php               # Se ha implementado para procesar las citas guardadas en la Tabla de Citas
│   │       ├── TableHTML.php                           # Se ha implementado para procesar la información que se renderizara en cada parte de la Tabla de Citas (contexto, referencias, estilo de cita)
│   │       └── PublicationJATSUploadForm.inc.php       # Se ha añadido una funcionalidad: Un nuevo FieldHTML que renderizara el HTML correspondiente a la Tabla de Citas  
│   ├── daos/                                           # Nueva carpeta con objetos de acceso a datos
│   │   └── CustomPublicationSettingsDAO.inc.php        # Se ha implementado para actualizar u obtener la configuración de la Tabla de Citas almacenada en la base de datos 
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
