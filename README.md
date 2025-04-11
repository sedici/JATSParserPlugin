# Documentación del Plugin jatsParser para OJS

## Introducción

El desarrollo que se ha llevado a cabo ha sido sobre un plugin ya existente llamado **jatsParser** creado por https://github.com/Vitaliy-1, el cual es utilizado en **OJS** (Open Journal Systems). En este trabajo se han implementado nuevas herramientas y funcionalidades. Esta documentación aborda aspectos técnicos sobre las modificaciones realizadas en el plugin.

## Instalación

1. Navega hasta la carpeta plugins/generic comenzando desde la raíz de OJS.
2. Ejecuta `git clone --recursive https://github.com/sedici/JATSParserPlugin.git jatsParser`.
3. Instalar las dependencias necesarias para la conversión de JATS a PDF: entra en la carpeta JATSParser con `cd jatsParser/JATSParser` y ejecuta `composer install`.  
4. Instalar las fuentes necesarias para generar el PDF: Ejecutar el script install-fonts.php. Para ello, dentro de la carpeta JATSParser dirijase a la carpeta install-fonts con `cd scripts/install-fonts`. Luego en la consola ejecute el siguiente comando: `php install-fonts.php` .

## Funcionalidad del Plugin

El propósito de este plugin es generar un documento **PDF** a partir de un archivo **XML** que sigue el estándar **JATS**.

Inicialmente, el PDF generado tenía una plantilla predefinida y  se han realizado modificaciones en la generación de la misma para:
- Cargar más metadatos desde OJS.
- Considerar las traducciones de metadatos como el título del artículo, subtítulo, resúmenes y palabras clave.
- Permitir la citación de referencias de acuerdo con el estilo de citación utilizado.
- Soporte para la creación de múltiples plantillas.
- Otras mejoras que se detallarán más adelante.

## Proceso de Generación del PDF

La generación del PDF en este plugin se divide en dos partes:

1. **Conversión del XML JATS a HTML:**
   - El **DOM** del XML JATS seleccionado desde la interfaz "JatsParser" (ubicada en la etapa de "Publicación" del artículo) es convertido a un **DOM HTML**.
   - Este nuevo **DOM HTML** contendrá los datos del contenido del artículo, que luego serán utilizados en la generación del PDF.

2. **Plantilla del PDF:**
   - La plantilla obtiene los **metadatos** desde OJS y los imprime en el PDF.
   - Es la primera sección visible del PDF generado antes de mostrar el contenido del artículo.
   - Se utiliza la librería **TCPDF** en PHP para la creación del documento PDF.

## Plantillas

Por el momento, se ha desarrollado una única plantilla de PDF llamada TemplateOne, pero en el futuro se añadirán más.

### Plantilla TemplateOne:

Se divide en cuatro secciones principales:

#### **TemplateBody**

Contiene los siguientes metadatos:
- Logo de la revista e institución.
- Número de la revista.
- DOI del artículo.
- ISSN de la revista.
- Link de la revista.
- Datos de los autores (nombre, ORCID, email, afiliación).
- Fechas de recepción, aceptación y publicación del artículo (esta última debe cargarse en "Fecha de publicación" dentro de la sección "Número" en la etapa de Publicación).

Se tuvo en cuenta la traducción de metadatos como título, subtítulo, resúmenes y palabras clave. Se implementó un **arreglo clave-valor** para manejar estas traducciones correctamente (el mismo se encuenta en la clase Translations).

#### **Header**
- Número de la revista.
- DOI del artículo.

#### **Footer**
- Licencia del artículo (se debe cargar la **URL de la licencia Creative Commons** en el campo "Licencia URL" dentro de la sección "Permisos y divulgación" en la etapa de Publicación).

Ejemplo de URL de licencia:
> https://creativecommons.org/licenses/by/4.0/

#### Body
- Todo el contenido del XML JATS del artículo subido a OJS.

---

## Estructura del Código

Para entender cómo se genera el PDF, debemos revisar el archivo `JatsParserPlugin.php`, ubicado en la carpeta raíz `jatsParser` del plugin.

Este archivo contiene la clase `JatsParserPlugin`, la cual gestiona el flujo del plugin. En esta clase se encuentra la función `register()`, encargada de registrar los **hooks** de OJS y asignarles funciones específicas.

### Hooks Modificados

#### **initPublicationCitation**
Se aplica al hook `publication::add`, que se ejecuta al aceptar un artículo. Esta función:
- Agrega una nueva fila en la tabla `publication_settings` de la base de datos.
- En el campo `setting_name`, se almacena el valor `jatsParser::citationTableData`.
- La función de esta tabla se explicará más adelante.

#### **editPublicationFullText**
Esta función invoca `getFullTextFromJats()`, encargada de convertir el **DOM XML JATS** en **DOM HTML**.

#### **createPdfGalley**
Esta función:
- Crea el PDF y lo agrega en la sección "Galeradas" de OJS en la etapa de Publicación.
- Llama a `pdfCreation()`, sobre la cual se realizaron modificaciones.

## Modificación de la Función pdfCreation()

Anteriormente, la función pdfCreation() se encargaba de instanciar la clase TCPDF y de construir cada parte del PDF, obteniendo directamente los metadatos desde OJS, con el apoyo parcial de una clase llamada TCPDFDocument.

Con el objetivo de mejorar la organización del código y facilitar su mantenimiento, se realizó una refactorización que permitió separar la lógica de creación del PDF de la gestión de metadatos. De esta manera, cada responsabilidad quedó delimitada, lo que favorece futuras modificaciones.

### Nuevo Flujo de `pdfCreation()`

1. Se llama a `getMetadata()`, que devuelve un arreglo `['clave' => 'valor']` con los metadatos necesarios para el PDF.
2. Se instancia `Configuration`, la cual recibe los metadatos obtenidos para almacenarlos en el atributo $config.
3. Se instancia `TemplateStrategy`, que recibe 2 parámetros: el **nombre de la plantilla ($templateName)** y la **configuración de metadatos ($config)**.
4. Aplicar el patrón de diseño **Strategy**, permitiendo la creación de múltiples plantillas en el futuro.
5. Instanciar un objeto que referencia a la plantilla correspondiente (por ejemplo: `TemplateOne`, por ahora la única plantilla disponible).
6. Al instanciar la plantilla, se ejecuta el constructor de `BaseTemplate`. Por eso, es importante que toda plantilla lo extienda correctamente.
7. `BaseTemplate` ejecuta el constructor de `GenericTemplate`, donde se inicializan y configuran los atributos iniciales del PDF. Además, se renderizan los componentes específicos de la plantilla: `Header`, `TemplateBody`, `Footer` y `Body`.

---

### ¿Como funciona `TemplateStrategy`? 

La clase `TemplateStrategy` implementa el patrón **Strategy**, lo cual permite seleccionar dinámicamente qué plantilla utilizar en función del nombre recibido. Este diseño facilita la escalabilidad del sistema, permitiendo agregar nuevas plantillas y seleccionarlas sin modificar la lógica principal de creación de PDFs.

Al instanciar esta clase, se le pasa como parámetro el nombre de la plantilla (por ejemplo, "TemplateOne") y una instancia de Configuration, que contiene la configuración necesaria para la generación del PDF.
Internamente, TemplateStrategy utiliza el nombre recibido para construir dinámicamente el namespace completo de la clase de plantilla correspondiente, siguiendo la convención: 

`JATSParser\\PDF\\Templates\\{$templateName}\\{$templateName}`

Luego, instancia dicha clase y la retorna. Esto permite que la lógica de selección de plantillas sea completamente dinámica y extensible, sin tener que realizar cambios en TemplateStrategy al incorporar nuevas plantillas.

### ¿Como funciona `BaseTemplate`? 

La clase abstracta BaseTemplate actúa como la base para todas las plantillas específicas. Cada plantilla concreta (como TemplateOne) debe extender de esta clase.
Cuando se instancia una plantilla, se ejecuta el constructor de BaseTemplate, el cual realiza dos tareas principales:

1. **Registro automático de componentes:** Mediante *reflection*, se detecta el namespace de la plantilla y se registran los componentes correspondientes (Header, TemplateBody, Footer, Body).
2. **Inicialización de la plantilla base:** Llama al constructor de GenericTemplate, pasándose a sí misma y la configuración.

Esto garantiza que cada plantilla use sus propios componentes personalizados, definidos en su namespace.

### ¿Como funciona `GenericTemplate`? 

La clase abstracta GenericTemplate extiende de TCPDF e incorpora la lógica general para configurar y generar el PDF.
Su constructor recibe una instancia de BaseTemplate (es decir, una plantilla concreta) junto con la configuración, y lleva a cabo las siguientes tareas:

1. **Inicialización de componentes:** Se crean instancias de los componentes registrados por BaseTemplate.
2. **Configuración general del PDF:** Se establecen metadatos, márgenes, auto saltos de página y otros aspectos del documento.
3. **Renderizado de componentes:** Se renderizan los componentes en orden:
      - TemplateBody (estructura general)
      - Header (cabecera)
      - Footer (pie de página)
      - Body (contenido principal del XML JATS del artículo subido a OJS)
   
La clase también define métodos públicos como Header(), Footer(), Body() y TemplateBody(), que se ejecutan automáticamente durante la generación del PDF. Cada uno de ellos se encarga de renderizar una sección específica del documento, delegando esta tarea al método render() del componente correspondiente.

Este enfoque promueve una clara separación de responsabilidades y facilita el mantenimiento del código. Además, permite personalizar fácilmente el comportamiento de cualquier sección en plantillas específicas, sobrescribiendo los métodos correspondientes en sus propios componentes. Así, cada plantilla puede tener su estilo y lógica propios, sin afectar a las demás.

---

### TemplateOne y la Configuración de PDF

En `TemplateOne`, se trabaja con una configuración recibida como parámetro. Esta clase, `Configuration`, está dentro de la carpeta `PDFConfig`.

Se encuentran definidos tres arreglos clave:

- **`$config`**: Contiene la configuración general utilizada para acceder a los metadatos y la configuración propia de la plantilla PDF y el estilo del artículo.
- **`metadata`**: Contiene todos los metadatos utilizados en la creación de la plantilla.
- **`template_body`**: Contiene los estilos para los metadatos del cuerpo de la plantilla.

#### Estructura de `$config`

```php
'header' => Contiene los estilos para los metadatos del HEADER
'footer' => Contiene los estilos para los metadatos del FOOTER
'body' => Contiene los estilos para el BODY (artículo científico)
'template_body' => Contiene los estilos para los metadatos del body de la plantilla
```

#### Acceso a la Configuración

Desde la plantilla (`TemplateOne`), se puede acceder a la configuración mediante métodos `get(NombreParte)Config`.

Por ejemplo, para obtener la configuración del encabezado:

```php
$this->config->getHeaderConfig();
```

Esto retornará un arreglo con la configuración del `header` y los metadatos:

```php
[
    'config' => { datos para el header del arreglo $config de Configuration },
    'metadata' => { todos los metadatos }
]
```

Este mismo patrón se repite para:

- `getTemplateBodyConfig()`
- `getFooterConfig()`
- `getBodyConfig()`

### Estilos de Citación Soportados

La clase `Configuration` define dos arreglos relacionados con los estilos de citación:

- **`$supportedCustomCitationStyles`**: Define los estilos de citación personalizados que mostrarán una tabla para conectar las citas con las referencias en el formato deseado (actualmente solo soporta APA).
- **`$numberedReferencesCitationStyles`**: Contiene los estilos de citación que tendrán referencias numeradas en el PDF (por ejemplo, IEEE usa referencias numeradas, mientras que APA no).

### Funcionalidad de `Body()`

La función `Body()` es llamada en el constructor de la plantilla. Dentro de esta función, se invoca el método `_prepareForPdfGalley()` de la clase `PDFBodyHelper`.

Este método:

- Recorre el DOM HTML del artículo científico.
- Adapta el contenido para su generación en PDF.
- Realiza consultas con `XPath` para acomodar figuras y tablas.
- Si el lenguaje de citación está soportado en `$supportedCustomCitationStyles`, usa `CustomPublicationSettingsDAO` para obtener datos de la base de datos, consultando la tabla `publication_settings`.

### Traducciones en `PDFConfig`

La clase `Translations` en `PDFConfig` contiene un arreglo con traducciones para diferentes idiomas.

#### Estructura del Arreglo de Traducciones

```php
[
    'en_EN' => [
        'abstract' => 'Abstract',
        'received' => 'Received',
        'accepted' => 'Accepted',
        'published' => 'Published',
        'keywords' => 'Keywords',
        'license_text' => 'This work is under a Creative Commons License',
        'references_sections_separator' => '&'
    ],
    'es_ES' => [
        'abstract' => 'Resumen',
        'received' => 'Recibido',
        'accepted' => 'Aceptado',
        'published' => 'Publicado',
        'keywords' => 'Palabras clave',
        'license_text' => 'Esta obra está bajo una Licencia Creative Commons',
        'references_sections_separator' => 'y'
    ],
    'pt_BR' => [
        'abstract' => 'Resumo',
        'received' => 'Recebido',
        'accepted' => 'Aceito',
        'published' => 'Publicado',
        'keywords' => 'Palavras chave',
        'license_text' => 'Este trabalho está sob uma licença Creative Commons',
        'references_sections_separator' => 'e'
    ]
];
```

### Importancia de las Traducciones

Las traducciones son utilizadas para generar el PDF en distintos idiomas. Los metadatos pueden estar cargados en diferentes idiomas, por lo que estas traducciones son necesarias para generar correctamente cada versión.

Ejemplo:

- En español: `Resumen`
- En inglés: `Abstract`
- En portugués: `Resumo`

Actualmente, los idiomas soportados son:

- **Inglés**
- **Español**
- **Portugués**

Se pueden agregar más idiomas según se requiera en futuras versiones del plugin.

---

# 📄 Creación de Nuevas Plantillas

Para agregar nuevas plantillas correctamente, se deben seguir los siguientes pasos (utilizar como referencia la plantilla TemplateOne):

## 1. 📁 Crear la Carpeta de la Plantilla

Crear una carpeta con el nombre de la nueva plantilla dentro del siguiente directorio: `jatsParser/JATSParser/PDF/Templates`

> Ejemplo:  
> `jatsParser/JATSParser/PDF/Templates/{NombreDePlantillaNueva}`

---

## 2. 🧱 Estructura Básica

Dentro de la nueva carpeta:

- Crear una subcarpeta llamada `Components`.
- Crear un archivo `.php` con el **mismo nombre** que la carpeta.  
  Por ejemplo: `NombreDePlantillaNueva.php`

### En `NombreDeLaPlantilla.php`:

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

## 3. 🧩 Crear los Componentes

Dentro de Components/, crear los siguientes archivos:

- TemplateBody.php
- Header.php
- Footer.php
- Body.php

### Estructura de cada componente: 

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

✅ Asegurate de:

- Usar el namespace correcto.
- Que la clase tenga el mismo nombre que el archivo.
- Que la clase extienda de GenericComponent.
- Definir el método render().

## 4. 🧠 Uso de $pdfTemplate en render()

Dentro del método render(), se puede usar $this->pdfTemplate para acceder a los métodos de TCPDF como: GetX(), GetY(), SetFont(), SetColor(), Cell(), MultiCell(), etc.

Además del uso de métodos propios de TCPDF, se pueden utilizar métodos personalizados llamados Renderers. Estos fueron creados para simplificar la impresión de datos específicos (como autores, licencias, etc.) y pueden ser usados en cualquier plantilla, incluso si originalmente fueron pensados para una sola.
  
## 5. 🧩 ¿Qué son los Renderers?

Los Renderers son funciones reutilizables que encapsulan la lógica de impresión o procesamiento de metadatos en el PDF. Están organizados en dos tipos:

- *SingleRenderers*: imprimen información puntual.
  Ej: ClickableOrcidLogo, License.

- *GroupRenderers*: imprimen bloques de información.
  Ej: AuthorsData, AbstractAndKeywords.

📁 Se encuentran en:
/JATSParser/PDF/Templates/Renderers

## 6. ➕ Crear un Nuevo Renderer

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

## 7. 🧪 Usar un Renderer en un Componente

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

# JatsParser: Tabla de Citas

En pleno desarrollo surgió una temática que resultó representar problema: de qué forma citar una referencia. Esto porque la forma de citar una referencia en IEEE, por ejemplo, es diferente a como se hace en APA.

Por ejemplo, en IEEE, para citar se utilizan los corchetes `[]`, y las referencias además deben estar numeradas. Dentro de los corchetes se indica con un número la referencia a la cual se está citando.

Si utilizamos una herramienta como el plugin **Texture** de OJS, al querer indicar cada cita con sus respectivas referencias, se pondrá como texto de la cita `[1]`, por ejemplo. Esto se debe a que este plugin solo soporta IEEE como estilo de citación.

Esto resultó ser un problema si se está trabajando en APA, ya que el texto de la cita no debería decir `[1]` o `[2]`, sino que debería aparecer algo como `(Giménez, 2025)`, o `(Giménez, 2025, pp. 15)`.

Es por eso que se optó por desarrollar una nueva funcionalidad en este plugin **JatsParser**: **La tabla de citas**.

## La tabla de citas

Esta tabla de citas por el momento solo aparece si desde la configuración de **JatsParser** (en la sección de Módulos instalados de OJS) se indica que se está trabajando con APA. A futuro se quiere implementar una tabla para cada estilo de citación.

### Componentes de la tabla de citas

- **Contexto:** Es una porción de texto que hace referencia a dónde se está queriendo agregar la cita. Es como una ayuda visual para saber en qué parte del artículo se está citando. Esto se indica mostrando las 50 palabras (si existen) antes de donde se indicó que hay una cita.
  
- **Referencias:** Son aquellas referencias que están siendo citadas. Por ejemplo, si en una cita se citan 4 referencias, estas referencias aparecerán en la tabla bajo un contexto.

- **Estilo de citación:** Menú desplegable en el cual se indicará el texto que queremos que aparezca en cada cita. En **APA** hay 3 opciones posibles: 
  1. `(Apellido, año)`
  2. `(Año)`
  3. "Otro" – Al hacer clic sobre esta opción, se abrirá un input text donde se puede especificar un texto personalizado.

Una vez seleccionado el estilo de citación para cada cita, debemos guardar los cambios haciendo clic sobre el botón **"Guardar citas"**. Esto guardará un JSON en la base de datos, específicamente en la fila `"jatsParser::citationTableData"` de la tabla `"publicationsettings"`. Es importante destacar que cada artículo tendrá su propia tabla y configuraciones guardadas. A la hora de guardar el JSON en la base de datos, se tiene en cuenta el **ID de la publicación**, el cual se indica en esta misma tabla bajo el nombre `"publication_id"`.

### Ayuda visual y colores

En la tabla se pueden ver cambios visuales según el estado de cada opción seleccionada en la columna **Estilo de Cita**:

- **Color verde:** Es la opción predeterminada. Si no hay citas guardadas aún para un artículo, se mostrarán todas las opciones por defecto `(apellido, año)`.  
  En caso de guardar las citas, al recargar la página, las citas se cargarán desde la base de datos y quedará seleccionada de forma predeterminada la última opción cargada en **Estilo de Cita** para cada cita. Esto servirá para no tener que recargar todos los datos desde un principio si por error se reinicia o se cierra la página, o si ocurre cualquier tipo de problema.
  
- **Color amarillo:** Aparece cuando se cambia de opción, es decir, la opción seleccionada **NO es la opción predeterminada** obtenida desde la base de datos.

- **Color rojo:** Indica un problema. Este aparece, por ejemplo, cuando se quieren cargar campos vacíos luego de hacer clic en "Otro" en el menú de selección de la columna **Estilo de Cita**. Se indicará un mensaje de error y los bordes del input text tendrán un color rojizo, para ayudar a los usuarios a saber cuáles son los campos incorrectos.

### Generación del PDF

Al generar el PDF, una vez indicados los estilos de cita para todas las citas, en el documento esto se verá reflejado. En el artículo, donde antes había `[1]` o `[2]`, ahora estará el texto que previamente cargamos en la tabla.

**Importante:** Recordar que siempre que se haga un cambio en un estilo de cita de la tabla, se deben guardar los cambios para que estos se reflejen al generar el PDF.

---

# Descripción del Desarrollo

Este desarrollo se puede ver en la ruta `JATSParser/classes`.

Como se puede observar en este directorio, encontramos una carpeta llamada `components` y otra llamada `daos`.

Dentro de la carpeta `components`, encontramos dos clases: `PublicationJATSUploadForm` y `TableHTML`.

### Clase `PublicationJATSUploadForm`

En la clase `PublicationJATSUploadForm` (que anteriormente ya formaba parte del plugin `JATSParser`), se trabaja toda la sección "JATSParser" en la etapa de publicación del artículo. Aquí se implementan los botones y los campos específicos para esa sección, es decir, todo lo que se mostrará al usuario.

Lo que se ha modificado es la implementación de un nuevo campo en la tabla, un `FieldHTML`, que será el encargado de mostrar la "Tabla de Citas" explicada anteriormente.

Para mostrar esta tabla de citas, se llama a un método estático de la clase `Configuration`. Aquí es donde se utiliza el arreglo de estilos de citación soportados. Se verifica si el estilo de cita seleccionado desde la configuración del plugin (en la clase se encuentra en una variable llamada `$citationStyle`, la cual se recibe desde un metadato cargado en OJS) existe en el arreglo de lenguajes soportados para la tabla (`$supportedCitationStyles`). Si existe, en la sección `JATSParser` de la etapa de publicación se mostrará la tabla; si no, no se mostrará nada.

### Clase `TableHTML`

Antes de crear el `FieldHTML` que generará la tabla, se instancia una clase llamada `TableHTML`, que recibe como parámetros el estilo de cita seleccionado en la configuración, la ruta absoluta del archivo XML seleccionado (para poder cargar el DOM de ese archivo y recuperar sus datos), y un arreglo llamado `$customCitationData`, que es el arreglo obtenido desde la base de datos. Este arreglo contiene, si para un artículo ya se han guardado citas, varios datos, entre ellos, los IDs de las citas y lo seleccionado en la columna "Estilo de Cita" en la Tabla de Citas.

#### Obtener los Datos desde la Base de Datos

En la variable `$customPublicationSettingsDao` se guarda la instancia de un objeto llamado `CustomPublicationSettingsDao()`.

Este objeto se encuentra dentro de la carpeta `daos` mencionada anteriormente y tiene dos métodos:

1. **`getSetting`**: Recibe el ID de la publicación (artículo), el nombre de la configuración a buscar en la tabla `publicationsettings` y el `localeKey` (por ejemplo, `es_ES`). El `localeKey` es importante, ya que para diferentes idiomas podemos tener una configuración de citas distinta y además distintas traducciones en la tabla de citas. Este método busca una coincidencia de la fila `jatsParser::citationTableData` en la tabla `publication_settings` en la base de datos, teniendo en cuenta que debe coincidir el ID de la publicación y el `localeKey` recibidos como parámetros.

   Si se encuentra una coincidencia, se retorna un arreglo con los datos cargados en la base de datos (lo que se retorna desde la base de datos es un JSON, pero mediante la función `json_decode`, se convierte a un arreglo).

   **Nota**: Es importante tener en cuenta que si para un artículo en un idioma determinado **NO** se han guardado las citas desde el botón "Guardar citas" presente en la Tabla de Citas, los datos que se mostrarán en las opciones de la columna "Estilo de Cita" serán por defecto (es decir, `Apellido, Nombre`). Si se han guardado las citas para ese idioma y artículo específico, entonces no se mostrarán los valores por defecto, sino lo último que se haya seleccionado como valor de opción para cada cita.

2. **`updateSetting`**: Se ejecuta al hacer clic en el botón "Guardar Citas" en la Tabla de Citas. Este método se encarga de insertar en la base de datos toda la configuración necesaria referida a ese artículo en ese idioma específico. Primero, verifica si ya existe alguna ocurrencia teniendo en cuenta el ID de la publicación y el `localeKey`. Si hay ocurrencias, solo actualiza el campo `setting_value` referido a ese artículo y idioma específico. Si no existe ninguna ocurrencia, inserta el valor por primera vez en la fila con un `setting_name` de `jatsParser::citationTableData`, respetando que el ID de la publicación de esa fila sea igual al ID de la publicación recibido por parámetro, y lo mismo para el `localeKey`.

   El llamado a este método se realiza en el archivo `process_citations.php`, el cual será explicado posteriormente.

#### Entendiendo la Clase `TableHTML`

La clase `TableHTML` se encarga de procesar y crear un arreglo que será utilizado para renderizar el HTML que muestre la tabla de citas.

Este arreglo se crea siguiendo los siguientes pasos:

1. **Instanciación de `DOMDocument`**: Se instancia un `DOMDocument` y se carga la ruta del archivo XML que se va a procesar (recibida como parámetro).

   Este DOM se utiliza para instanciar una clase `DOMXPath`, almacenada bajo el nombre `xpath`, que será utilizada para hacer el procesamiento posterior del DOM del XML JATS.

2. **Llamada a `extractXRefs`**: En el constructor de la clase, se llama a la función `extractXRefs()`. Esta función realiza una consulta `xpath` para buscar en el DOM del documento XML todas las citas. Las citas en un XML JATS aparecen en el elemento `<body>` bajo una etiqueta llamada `<xref>`, que contiene atributos como el ID de cita (un identificador único) y `rid` (hace referencia a las citas que son citadas, por ejemplo, si el `rid` dice `parser0 parser1 parser2`, esto significa que se están citando las referencias con los IDs `parser0`, `parser1`, y `parser2`).

   Cada cita encontrada es procesada para obtener las 50 palabras anteriores desde el lugar donde fue marcada, lo que conocemos como "Contexto" en la Tabla de Citas. Si se definen dos o más citas en el mismo párrafo con el mismo atributo `rid`, se marca la cita para evitar problemas de procesamiento.

   La cantidad de palabras que se toman antes de la cita está definida en la constante `CITATION_MARKER`, la cual está originalmente configurada en 50, pero se puede modificar.

   Finalmente, en el arreglo `$xrefsArray` se guardan el contexto, el `rid`, y el texto original de la cita.

3. **Llamada a `extractReferences`**: Se invoca la función `extractReferences()`, que realiza una consulta `xpath` y genera un arreglo que contiene, para cada ID de referencia (como `parser0`), el texto completo de la referencia y un arreglo con los autores indicados en esa referencia.

   Las referencias en XML JATS están en el elemento `<back>`. Cada referencia está contenida en un elemento `<ref>`, con un atributo `id` (como `parser0`). Esta referencia contiene elementos como `<mixed-citation>`, que tiene el texto completo de la referencia, y `<element-citation>`, que contiene cada parte de la referencia (fecha, autores, título, etc.).

4. **Llamada a `mergeArrays`**: Finalmente, se llama al método `mergeArrays()`, que combina los dos arreglos generados anteriormente (`$xrefsArray` y `$referencesArray`) en un solo arreglo llamado $arrayData con la siguiente estructura:

```php
[
    'xref_id1' => [
        'status' => 'default',
        'citationText' => '',
        'context' => 'Contexto 1',
        'rid' => 'parser_0 parser_1',
        'references' => [
            [
                'id' => 'parser_0',
                'reference' => 'Referencia 1',
                'authors' => [
                    'data_1' => [
                        'surname' => 'Smith',
                        'year' => '2020'
                    ],
                    'data_2' => [
                        'surname' => 'Johnson',
                        'year' => '2020'
                    ]
                ]
            ],
            [
                'id' => 'parser_1',
                'reference' => 'Referencia 2',
                'authors' => [
                    'data_1' => [
                        'surname' => 'Doe',
                        'year' => '2019'
                    ]
                ]
            ]
        ]
    ],
    'xref_id2' => [
        'status' => 'not-default',
        'citationText' => '(Smith y Johnson, 2020; Doe et al, 2019)',
        'context' => 'Contexto 2',
        'rid' => 'parser_1',
        'references' => [
            [
                'id' => 'parser_1',
                'reference' => 'Referencia 1',
                'authors' => [
                    'data_1' => [
                        'surname' => 'Doe',
                        'year' => '2019'
                    ]
                ]
            ]
        ]
    ]
]
```

Luego de crear el arreglo `$arrayData`, el constructor llama al método `makeHtml()`.

Este método almacena en una variable `$classname` la concatenación del namespace, el estilo de citación (APA, AMA, IEEE, etc.) con la primera letra en mayúscula y la palabra "Style". A continuación, se llama a la función `processContexts()`, que recibe como parámetro el arreglo `$arrayData`.

La función `processContexts()` itera sobre el arreglo recibido utilizando un `foreach` de la siguiente manera:

```php
foreach ($data as $xrefId => &$item) {
    // código...
}
```

Esta iteración se hace para saber qué texto mostrar en el lugar de la cita dentro del contexto. Esta es la razón por la cual, en el arreglo `$xrefsArray`, se guarda el valor `originalText` como valor de cada cita. En caso de que el valor asociado a la clave `citationText` del arreglo `$item` **NO** esté vacío, esto quiere decir que ya hay un texto para esa cita cargado y, por lo tanto, en el contexto, lo que debería aparecer en el lugar donde está la cita es ese texto de forma predeterminada. En caso de que esté vacío, se almacenará en la variable `$citationText` el valor asociado a la clave `originalText` (este valor contendrá lo que tenía inicialmente el elemento `<xref>` de la cita); si, por ejemplo, se usa el plugin **TEXTURE**, se mostrará algo como `[1]`, por ejemplo. Esto en la Tabla de Citas se muestra cuando, para alguna o algunas citas, aún no se ha guardado ningún valor, siendo esta la forma de mostrar el texto en su forma "Default".

#### ¿Cuándo se guarda este texto?

- Cuando en la tabla se hace click en "Guardar Citas". Esto hace que las citas se guarden en formato JSON en la base de datos, tal y como se explicó antes; eso quiere decir que ya va a haber un texto cargado para cada cita. Aquí, en este método, es donde se verifica.

#### ¿Cómo identifico el lugar donde tengo que poner el texto dentro del contexto?

- En una parte del método `extraxtXRefs()`, podemos ver que en la variable `$context` se guarda `$beforeWords` (50 palabras anteriores a la cita), concatenadas con un espacio en blanco y una constante `self::CITATION_MARKER`. Esta constante es un identificador o marca que tendrá el contexto, para que, cuando se procese en `processContexts()`, se reconozca dónde se debe poner el texto (ya sea el texto por default contenido en el elemento `<xref>` de la cita o el texto guardado en la base de datos, como "(Giménez, 2025)", por ejemplo).

---

Luego de esto, dentro de cada contexto, en el lugar donde está el identificador o marca para el texto de la cita se agregan algunos estilos para mostrarlo de color azul (sirve de ayuda visual), para finalmente reemplazarla y, en su lugar, colocar el texto de la cita con sus correspondientes estilos. Este nuevo contexto modificado se guardará para esa cita (arreglo `$item`) bajo la clave `context`.

Esto sirve para poder verificar qué texto se muestra en el lugar de la cita dentro del contexto, ya sea un valor por default o el que cargó el usuario desde la Tabla de Citas. Esto ayudará para que no se tenga que volver a cargar para cada cita su estilo de citación en caso de que haya algún inconveniente, evitando tener que rehacer todo nuevamente.

Al finalizar este método `makeHtml`, se llama al método estático `makeHtml` de la clase `$className`. La clase que se llame dependerá del estilo de citación seleccionado en la configuración del plugin. Por ejemplo, si se está trabajando en APA, se llamará a la clase `ApaStyle`; si es IEEE, se llamará a `IeeeStyle`, y así sucesivamente con cada estilo de citación.

La idea es que cada estilo de citación tenga su propia plantilla HTML (este HTML representará a la Tabla de Citas mostrada). Por el momento, solo está creada la plantilla para APA (clase `ApaStyle`), pero si se desea agregar una nueva plantilla para un estilo de citación se pueden seguir los siguientes pasos:

1. Asegurarse de que el estilo de citación esté definido en el arreglo `$supportedCustomCitationStyles` (agregar **en minúscula** la clave del estilo de citación al que se le desea agregar soporte).

2. Crear un archivo que siga la estructura: `{estiloDeCitación}Style.php` dentro del directorio `/components/form/CitationStyles/`.  
   Por ejemplo, si se desea agregar una plantilla para el estilo de citación Vancouver, se debe crear un archivo llamado `VancouverStyle.php` en la ruta especificada.

3. En el nuevo archivo creado, agregar el siguiente `require_once`:
   ```php
   require_once __DIR__ . '/../Helpers/process_citations.php';
   ```
   
4. Asegurarse de que el nombre de la nueva clase sea el mismo que el nombre del archivo.

5. Declarar el método estático `makeHtml` que reciba los siguientes parámetros en el orden indicado:  
   - El arreglo con todos los datos de la tabla (`$arrayData`),  
   - La ruta absoluta del XML (`$absoluteXmlPath`),  
   - El estilo de citación seleccionado desde la configuración del plugin (`$citationStyle`),  
   - El ID de la publicación (`$publicationId`),  
   - La key del idioma local (`$locale_key`).  

Estos parámetros se reciben desde la clase `TableHTML`.

6. Diseñar el formulario HTML necesario para la nueva Tabla de Citas.

Para crear la plantilla se puede seguir como ejemplo la clase `ApaStyle`, la cual puede servir de ayuda para entender cómo se desarrolló la misma.

---
