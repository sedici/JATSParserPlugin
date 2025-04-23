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
│   │           ├── PDFBodyHelper.php                   # Se ha añadido la funcionalidad de procesar el contenido XML JATS para estructurar de forma correcta el cuerpo del PDF si el articulo esta en APA 
│   │           ├── TemplateStrategy.php                # Se ha implementado para manejar plantillas dinamicamente implementando un Strategy Pattern
│   │           ├── PDFConfig/                          # Se ha definido una carpeta para almacenar la configuracion centralizada para la generacion de PDFs
│   │           │   ├── Configuration.php               # Se ha implementado para centralizar metadatos y estilos 
│   │           │   └── Translations.php                # Se ha implementado para almacenar traducciones para textos especificos en PDFs generados 
│   │           └── Templates/                          # Se ha definido un sistema de plantillas modulares y extensibles
│   │               ├── Renderers/                      # Se ha implementado un sistema de renderizado reutilizable con separacion de responsabilidades
│   │               │   ├── GroupRenderer/              # Se implementaron renderizadores para elementos compuestos (resumenes en diferentes idiomas, informacion completa de autores, etc) 
│   │               │   └── SingleRenderer/             # Se implementaron renderizadores para elementos atomicos (textos, imagenes, licencia, etc)
│   │               ├── BaseTemplate.php                # Se ha implementado como clase base abstracta con metodos comunes para todas las plantillas. Reconoce los componentes de cada plantilla.
│   │               ├── GenericTemplate.php             # Se ha implementado como clase base que inicializa los componentes correspondientes a la plantilla reconocida por BaseTemplate.
│   │               ├── GenericComponent.php            # Se ha implementado como clase base para todos los componentes con funcionalidad compartida
│   │               └── TemplateOne/                    # Se ha implementado una plantilla personalizada llamada "TemplateOne"
│   │                   ├── TemplateOne.php             # Se ha implementado la clase principal de la plantilla. Carga sus componentes correspondientes que luego seran procesados en BaseTemplate.php
│   │                   └── Components/                 # Componentes especificos de esta plantilla
│   │                       ├── Body.php                # Renderiza el contenido del XML JATS del articulo (incluidas las referencias bibliograficas) 
│   │                       ├── Footer.php              # Renderiza el pie de pagina con la informacion de la licencia llamando al Renderer individual "Licence" 
│   │                       ├── Header.php              # Renderiza el encabezado llamando a Renderers especificos segun los elementos que se deseen imprimir
│   │                       └── TemplateBody.php        # Renderiza la caratula del articulo con datos introductorios, utilizando Renderers especificos segun los elementos que se deseen imprimir
│   ├── scripts/                                        # Se ha añadido esta carpeta que contiene los Scripts necesarios para el funcionamiento del plugin
│   │   └── install-fonts/                              
│   │       └── install-fonts.php                       # Script de instalacion automatica de fuentes personalizadas para TCPDF
│   ├── vendor/
│   ├── logo/                                           # Se han añadido logos que son utilizados para la generacion del PDF, tales como como el logo ORCID o los logos correspondientes a los tipos de licencias Creative Commons                                                 
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
│   │       │   ├── Stylesheets/                        # Se ha creado una carpeta que almacena los estilos para cada formato de citacion (actualmente solo se soporta APA)
│   │       │   │   ├── ApaStylesheet.php               # Estilos especificos para formato APA 
│   │       │   │   └── GenericStylesheet.php           # Clase base abstracta con estilos comunes
│   │       │   ├── ApaCitationTable.php                # Implementacion de una tabla de citas con estilo de citacion APA
│   │       │   └── GenericCitationTable.php            # Se ha implementado una clase base con un patron Template Method para tablas de citacion
│   │       ├── Helpers/                                # Se ha agregado una carpeta con funciones auxiliares para el procesamiento de formularios
│   │       │   └── process_citations.php               # Se ha implementado para procesar las citas guardadas en la Tabla de Citas
│   │       ├── TableHTML.php                           # Se ha implementado para procesar la informacion que se renderizara en cada parte de la Tabla de Citas (contexto, referencias, estilo de cita)
│   │       └── PublicationJATSUploadForm.inc.php       # Se ha añadido una funcionalidad: Un nuevo FieldHTML que renderizara el HTML correspondiente a la Tabla de Citas  
│   ├── daos/                                           # Nueva carpeta con objetos de acceso a datos
│   │   └── CustomPublicationSettingsDAO.inc.php        # Se ha implementado para actualizar u obtener la configuracion de la Tabla de Citas almacenada en la base de datos 
│   └── JATSParserDocument.inc.php
│
└── **archivos específicos del plugin**
```

#### 📁 **Archivos y directorios clave** ***PARA LA GENERACIÓN DEL PDF***: ###
- `JatsParserPlugin.php`: Archivo principal que define el flujo del plugin y registra los hooks de OJS. Se realizaron modificaciones importantes en la función `pdfCreation()` para separar la lógica de obtención de metadatos de la generación del PDF. Ahora, esta función:
  👉 Obtiene los metadatos del artículo.
  👉 Instancia `Configuration.php` con esos datos.
  👉 Utiliza `TemplateStrategy` para seleccionar dinámicamente la plantilla a renderizar.
  👉 Exporta el PDF generado para su visualización dentro de OJS.

- `PDF/Templates/`: Contiene las plantillas utilizadas para generar el PDF, organizadas en carpetas individuales (por ejemplo, `TemplateOne/`). Cada plantilla incluye sus propios componentes (`Header`, `TemplateBody`, `Body`, `Footer`) y define cómo se renderiza cada sección del documento. También incluye los **Renderers reutilizables**, ubicados en `Renderers/`, que encapsulan la lógica para imprimir elementos específicos del PDF (como autores, licencias, palabras clave, etc.).

- `PDFConfig/`: Almacena la configuración general del PDF (fuentes, colores, etc.) en `Configuration.php`, y las traducciones multilenguaje en `Translations.php`. Esta configuración es utilizada por todas las plantillas para mantener coherencia visual y textual, y permite generar PDFs adaptados al idioma del contenido (actualmente soporta español, inglés y portugués).

#### 📁 **Archivos y directorios clave** ***PARA LA TABLA DE CITAS***: ###
- `forms/CitationStyles/`: Contiene las clases específicas que definen cómo se renderiza la Tabla de Citas para cada estilo de citación (por ejemplo, `ApaCitationTable.php`). Estas clases extienden de `GenericCitationTable` y definen cómo formatear citas con uno, dos o múltiples autores, además del separador entre citas. La carpeta `Stylesheets/` dentro de este directorio incluye archivos que encapsulan estilos comunes para reutilizar en múltiples estilos de citación.

- `forms/Helpers/process_citations.php`: Encargado de procesar y analizar las citas seleccionadas en la Tabla de Citas desde la interfaz de OJS. Este script construye un JSON con la configuración de citas obtenida desde el formulario y lo envía a `CustomPublicationSettingsDAO` para su lectura o actualización en la base de datos.

- `daos/CustomPublicationSettingsDAO.php`: Se encarga de acceder y actualizar la configuración de citas en la base de datos, la cual se almacena con el `setting_name` de `jatsParser::citationTableData` en la tabla `publication_settings`. Durante la generación del PDF, recupera la configuración correspondiente; y cuando se guardan las citas desde la Tabla de Citas, la información se actualiza o inserta según sea necesario.

- `forms/TableHTML.php`: Procesa el XML JATS del artículo para generar el contenido de la Tabla de Citas, incluyendo el contexto, las referencias y el estilo correspondientes.

**IMPORTANTE:** *Por el momento, la Tabla de Citas solo está diseñada con soporte para APA 7*

# 🔧 **Personalización y Extensiones**

---

## 📄 Creación de Nuevas Plantillas

Para agregar nuevas plantillas correctamente, se deben seguir los siguientes pasos (utilizar como referencia la plantilla TemplateOne):

### 1. 📁 Crear la Carpeta de la Plantilla

Crear una carpeta con el nombre de la nueva plantilla dentro del siguiente directorio: `jatsParser/JATSParser/PDF/Templates`

> Ejemplo:  
> `jatsParser/JATSParser/PDF/Templates/{NombreDePlantillaNueva}`


### 2. 🧱 Estructura Básica

Dentro de la nueva carpeta:

- Crear una subcarpeta llamada `Components`.
- Crear un archivo `.php` con el **mismo nombre** que la carpeta.  
  Por ejemplo: `NombreDePlantillaNueva.php`

#### En `NombreDeLaPlantilla.php`:

```php
// Reemplazar {NombreDePlantillaNueva} por el nombre específico de la nueva plantilla.

<?php namespace JATSParser\PDF\Templates\{NombreDePlantillaNueva}; 

//Importar BaseTemplate y los componentes específicos de la nueva plantilla
use JATSParser\PDF\Templates\BaseTemplate;
use JATSParser\PDF\Templates\{NombreDePlantillaNueva}\Components\TemplateBody;
use JATSParser\PDF\Templates\{NombreDePlantillaNueva}\Components\Header;
use JATSParser\PDF\Templates\{NombreDePlantillaNueva}\Components\Footer;
use JATSParser\PDF\Templates\{NombreDePlantillaNueva}\Components\Body;

class {NombreDePlantillaNueva} extends BaseTemplate
{
}
```

✅ Asegurate de:
- Usar el namespace correcto.
- Que la clase tenga el mismo nombre que el archivo.
- Que la clase extienda de BaseTemplate.


### 3. 🧩 Crear los Componentes

Dentro de Components/, crear los siguientes archivos:

- TemplateBody.php
- Header.php
- Footer.php
- Body.php

#### Estructura de cada componente: 

- Para el Header:

```php
// Reemplazar {NombreDePlantillaNueva} por el nombre específico de la nueva plantilla.

<?php namespace JATSParser\PDF\Templates\{NombreDePlantillaNueva}\Components;

use JATSParser\PDF\Templates\GenericComponent;

class Header extends GenericComponent
{
    public function render()
    {
        // Lógica del componente
    }
}
```

- Para el TemplateBody:

```php
// Reemplazar {NombreDePlantillaNueva} por el nombre específico de la nueva plantilla.

<?php namespace JATSParser\PDF\Templates\{NombreDePlantillaNueva}\Components;

use JATSParser\PDF\Templates\GenericComponent;

class TemplateBody extends GenericComponent
{
    public function render()
    {
        // Lógica del componente
    }
}
```

- Para el Body:

```php
// Reemplazar {NombreDePlantillaNueva} por el nombre específico de la nueva plantilla.

<?php namespace JATSParser\PDF\Templates\{NombreDePlantillaNueva}\Components;

use JATSParser\PDF\Templates\GenericComponent;

class Body extends GenericComponent
{
    public function render()
    {
        // Lógica del componente
    }
}
```

- Para el Footer:

```php
// Reemplazar {NombreDePlantillaNueva} por el nombre específico de la nueva plantilla.

<?php namespace JATSParser\PDF\Templates\{NombreDePlantillaNueva}\Components;

use JATSParser\PDF\Templates\GenericComponent;

class Footer extends GenericComponent
{
    public function render()
    {
        // Lógica del componente
    }
}
```


✅ Asegurate de:
- Usar el namespace correcto.
- Que la clase tenga el mismo nombre que el archivo.
- Que la clase extienda de GenericComponent.
- Definir el método render().

**Para probar la plantilla, puede dirigirse a la función pdfCreation() en JatsParserPlugin.php (en la carpeta jatsParser/, la raíz del plugin) y modificar el valor de la variable $templateName por el nombre de la nueva plantilla. Por ejemplo:**

```php

	//Dentro de JatsParserPlugin.php

	private function pdfCreation(string $htmlString, Publication $publication, Request $request, string $localeKey): string {
		$metadata = $this->getMetadata($publication, $localeKey, $request, $htmlString);
		$configuration = new Configuration($metadata);

		$templateName = '{NombreDeLaPlantilla}'; //Reemplazar {NombreDeLaPlantilla por el nombre de la nueva plantilla creada.}
		$templateStrategy = new TemplateStrategy($templateName, $configuration);

		return $templateStrategy->OutputPdf();
	}
```

### 4. 🧠 Uso de $pdfTemplate en render()

Dentro de los métodos render() definidos en cada uno de los componentes, se puede usar $this->pdfTemplate para acceder a los métodos de TCPDF como: GetX(), GetY(), SetFont(), SetColor(), Cell(), MultiCell(), etc.

Además del uso de métodos propios de TCPDF, se pueden utilizar métodos personalizados llamados Renderers. Estos fueron creados para simplificar la impresión de datos específicos (como autores, licencias, etc.) y pueden ser usados en cualquier plantilla, incluso si originalmente fueron pensados para una sola.

  
### 5. 🧩 ¿Qué son los Renderers?

Los Renderers son funciones reutilizables que encapsulan la lógica de impresión o procesamiento de metadatos en el PDF. Están organizados en dos tipos:

- *SingleRenderers*: imprimen información puntual.
  Ej: ClickableOrcidLogo, License.

- *GroupRenderers*: imprimen bloques de información.
  Ej: AuthorsData, AbstractAndKeywords.

📁 Se encuentran en:
/JATSParser/PDF/Templates/Renderers

### 6. ➕ Crear un Nuevo Renderer

Pasos:

1. Crear un archivo .php en:
   - GroupRenderer/ o SingleRenderer/

2. Definir el namespace:

```php
//Si estamos creando un GroupRenderer:
<?php namespace JATSParser\PDF\Templates\Renderers\GroupRenderer;

o

//Si estamos creando un SingleRenderer:
<?php namespace JATSParser\PDF\Templates\Renderers\SingleRenderer;

```

3. Definir una clase con un método público y estático:

```php
//Reemplazar {NombreDelRenderer} por el nombre específico del Renderer

class {NombreDelRenderer} {

   public static function render{NombreDelRenderer}($pdfTemplate, ...) {
        // Lógica del renderer
    }

⚠️ IMPORTANTE: El método debe recibir de forma obligatoria el parámetro $pdfTemplate, ya que es la instancia  sobre la cual se realizarán las operaciones. También puede recibir $config (Configuración del PDF) u otros parámetros específicos necesarios y trabajar con ellos en este método.
🔁 Seguir como patrón para el nombre del método: render{NombreDelRenderer}.   
}
```


### 7. 🧪 Usar un Renderer en un Componente

1. Dirigirse al componente de la plantilla donde se desea importar el Renderer.

2. Importar el Renderer:

```php
use JATSParser\PDF\Templates\Renderers\GroupRenderer\{NombreDelRenderer};

o

use JATSParser\PDF\Templates\Renderers\SingleRenderer\{NombreDelRenderer};
```

3. Usarlo en el método render() del componente, por ejemplo:

```php
{NombreDelRenderer}::render{NombreDelRenderer}(
   $this->pdfTemplate, // Es obligatorio. Es la instancia de la plantilla PDF (está almacenada en GenericComponent)
   $this->config, //Es opcional. Es la configuración de la plantilla PDF (está almacenada en GenericComponent)
   $this->pdfTemplate->GetX(), //Es opcional. Método que devuelve la posición de X en el PDF (es propio de TCPDF) 
   $this->pdfTemplate->GetY() //Es opcional. Método que devuelve la posición de Y en el PDF (es propio de TCPDF)
);
```

⚠️ Es obligatorio pasar `$this->pdfTemplate` como parámetro.
Además, En lugar de enviar $this->config, también se puede enviar una configuración más específica como:
`$this->config->getTemplateBodyConfig` 
o incluso valores definidos directamente en la clase.
💡 La implementación queda a criterio de cada desarrollador.

---
---
---

