# Documentación en Español del Plugin jatsParser para OJS

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

👉 3. Moverse a la rama stable-3_4:
```
git checkout stable-3_4
```

👉 4. Instalar las dependencias necesarias para la conversión de JATS a PDF: entra en la carpeta JATSParser con:
```bash
cd jatsParser/JATSParser
```
y dentro ejecuta el comando:
```bash
composer install
```
Si encuentras errores durante la instalación (comúnmente por falta de extensiones de PHP en el sistema), asegúrate de instalar los módulos requeridos y reintentar:
```
sudo apt update
sudo apt install php8.1-gd php8.1-mbstring php8.1-curl
composer install
```

## ⚙️ Funcionalidades y cambios principales

- Generación de PDF mediante la librería mPDF, utilizando plantillas personalizadas. Estas plantillas tienen 3 niveles de configuración: Principiante (logos), intermedio (CSS) y avanzado (TPLs)
- Generación de HTML para la previsualización de OJS.
- ***Impresión*** de metadatos en el PDF (como títulos, resúmenes, palabras clave, fechas, etc) ***en diferentes idiomas*** (actualmente solo se soporta español, inglés y portugués) 
- Interfaz visual en OJS para cargar citas según el estilo de citación y contexto del artículo: la Tabla de Citas.
- Estructura modular con ***Strategy Pattern*** para facilitar nuevos formatos de salida sin modificar la lógica central (actualmente solo se genera HTML y PDF).
- Mejora en el mapeo de metadatos para la generación de referencias bibliográficas mediante la librería CiteProc.
