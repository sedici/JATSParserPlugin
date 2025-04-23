# JATSParserPlugin para OJS 3.3

Este plugin extiende el [plugin original jatsParser](https://github.com/Vitaliy-1) y permite generar PDFs a partir de archivos XML con estándar JATS en OJS 3.3. Incorpora mejoras en la generación de PDFs, soporte multilenguaje, plantillas personalizadas, y un sistema de tablas de citas según el estilo de citación.

## 📦 Instalación

```bash
cd plugins/generic
git clone --recursive https://github.com/sedici/JATSParserPlugin.git jatsParser
cd jatsParser/JATSParser
composer install
cd scripts/install-fonts
php install-fonts.php
