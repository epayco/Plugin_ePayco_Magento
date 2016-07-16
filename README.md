# ePayco plugin para Magento v1.9.2.4

**Si usted tiene alguna pregunta o problema, no dude en ponerse en contacto con nuestro soporte técnico: desarrollo@payco.co.**

## Tabla de contenido

* [Requisitos](#requisitos)
* [Instalación](#instalación)
* [Pasos](#pasos)
* [Versiones](#versiones)


## Requisitos

* Tener una cuenta activa en [ePayco](https://pagaycobra.com).
* Tener instalado Magento.
* Acceso a las carpetas donde se encuetra instalado Magento.

## Instalación

1. [Descarga el plugin.](https://github.com/epayco/Plugin_ePayco_Magento/releases/tag/1.9.2.4)
2. En la ruta **app/etc/modules** Suba el archivo que esta en **app/etc/modules/Payco_Payco.xml**.
3. En la ruta **app/design/frontend/base/default/template** Suba los archivos que esta en **app/design/frontend/base/default/template/payco**.
4. En la ruta **app/locale/en_US** Suba el archivo **app/locale/en_US/Payco_Payco.csv**.
5. En la ruta **app/code/local** suba la carpeta **app/code/local/Payco**. Si no existe el directorio local debe crearlo y darle permisos de lectura y escritura respectivos.
6. Revise los pasos 2 a 5 que tengan la misma ruta y archivos.
7. Ingrese a su panel Administrador y borre la cache de Magento.
8. En su administrador de magento vaya a **System** / **Configuration** / **Sales** / **Payment** Methods Debe ver el medio de pago ePayco.
9. Ingrese el **P_CUST_ID_CLIENTE** y la **P_KEY** (Valores obtenidos en su panel de clientes).
10. Guarde la configuración, borre caches y verifique que todo este funcionando correctamente.
11. Haga pruebas de compras y en los metodos de pago debe aparecer ePayco.


## Pasos

<img src="ImgTutorialCS_CART/tuto-1.png" width="400px"/>
<img src="ImgTutorialCS_CART/tuto-2.png" width="400px"/>
<img src="ImgTutorialCS_CART/tuto-3.png" width="400px"/>
<img src="ImgTutorialCS_CART/tuto-4.png" width="400px"/>
<img src="ImgTutorialCS_CART/tuto-5.png" width="400px"/>
<img src="ImgTutorialCS_CART/tuto-6.png" width="400px"/>
<img src="ImgTutorialCS_CART/tuto-7.png" width="400px"/>
<img src="ImgTutorialCS_CART/tuto-8.png" width="400px"/>

## Versiones
* [ePayco plugin Magento v1.9.2.4](https://github.com/epayco/Plugin_ePayco_Magento/releases/tag/1.9.2.4).
* [ePayco plugin Magento v1.5.0.0](https://github.com/epayco/Plugin_ePayco_Magento/releases/tag/1.5.0.0).