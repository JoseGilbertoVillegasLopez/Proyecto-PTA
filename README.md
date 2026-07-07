# SistemaPTA

Sistema web para gestionar el **Plan de Trabajo Anual (PTA)** del Instituto Tecnológico Superior de Cosamaloapan. Digitaliza un proceso que antes se hacía en papel: creación de PTAs por departamento, seguimiento de valores alcanzados mes a mes y generación de reportes trimestrales.

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 + Symfony 7 |
| ORM | Doctrine |
| Base de datos | MySQL |
| Frontend | Bootstrap 5, JavaScript vanilla, Twig |
| Servidor local | XAMPP (`c:\xampp\htdocs\Proyecto-PTA`) |

---

## Módulos

### Núcleo del PTA

| Módulo | Estado | Descripción |
|---|---|---|
| **PTA** | En desarrollo | Creación y gestión del plan anual: indicadores, acciones, meses de ejecución, responsables (supervisor y aval), captura de avance mensual |
| **Reportes** | En desarrollo | Generación de reportes trimestrales por PTA; exportación a PDF y Word; galería de evidencias fotográficas |

### Indicadores institucionales

| Módulo | Estado | Descripción |
|---|---|---|
| **Indicadores básicos** | En desarrollo | Catálogo de indicadores agrupados en 5 categorías (Alumnos, Docentes, Extensión y Vinculación, Investigación, Administración), asignables a uno o más departamentos |
| **Reporte de indicadores** | En desarrollo | Reportes trimestrales de indicadores básicos por usuario/departamento: captura de actividades con evidencias, entrega y revisión por encargado |

### Catálogos

| Módulo | Estado | Descripción |
|---|---|---|
| **Personal** | En desarrollo | Gestión del personal del instituto |
| **Departamentos** | En desarrollo | Gestión de departamentos y asignación de indicadores básicos |
| **Puestos** | — | Gestión de puestos y cargos |
| **Partidas presupuestales** | — | Catálogo de partidas para reportes de gastos |
| **Proceso clave** | — | Catálogo de procesos clave |
| **Proceso estratégico** | — | Catálogo de procesos estratégicos |

### Accesos y operaciones

| Módulo | Estado | Descripción |
|---|---|---|
| **Usuarios** | En desarrollo | Gestión de cuentas vinculadas a Personal, roles, primer login con cambio de contraseña obligatorio y recuperación de contraseña |
| **Solicitud de gastos** | En desarrollo | Solicitud de viáticos y gastos al área de finanzas; flujo de revisión con 3 encargados por cargo (revisor/supervisor/autoriza) con votación y progreso en modal; comprobante de pago; exportación a PDF con el formato oficial de finanzas; CRUD de bancos |
| **Módulo de accesos** | En desarrollo | Gestión configurable desde la UI de admin de qué puestos tienen acceso y quién es encargado de cada módulo del sistema; elimina el hardcoding de roles en el código; soporta cargos por encargado (revisor/supervisor/autoriza) para módulos que lo requieran |

### Plataforma

| Módulo | Estado | Descripción |
|---|---|---|
| **Dashboard** | En desarrollo | Layout principal: sidebar de navegación condicionado por rol/acceso y Turbo Frame de contenido |

---

## Requisitos previos

> **Nota:** esta sección está incompleta — agregar versiones exactas de PHP, Composer y MySQL, y el comando de instalación de dependencias.

- PHP 8.2+
- Composer
- MySQL
- XAMPP (o servidor Apache equivalente)

---

## Instalación local

> **Nota:** pendiente documentar los pasos completos. A continuación el flujo general.

```bash
# 1. Clonar el repositorio
git clone <url-del-repositorio> Proyecto-PTA

# 2. Instalar dependencias PHP
composer install

# 3. Configurar variables de entorno
cp .env .env.local
# editar .env.local con la conexión a MySQL

# 4. Crear la base de datos y ejecutar migraciones
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. (Opcional) Cargar fixtures de prueba
# php bin/console doctrine:fixtures:load
```

---

## Estructura de carpetas relevante

```
src/
  Controller/       # Controllers Symfony por módulo
  Entity/           # Entidades Doctrine
  Form/             # FormTypes de Symfony
  Repository/       # Repositorios con queries personalizadas
  Service/          # Lógica de negocio separada por módulo
    Pta/            # Servicios del módulo PTA
    SolicitudGastos/
    ModuloAcceso/
    Indicadores/    # Reportes de indicadores básicos y ciclos

templates/          # Vistas Twig por módulo
public/
  js/               # JavaScript vanilla por módulo
  css/              # Estilos por módulo
  uploads/          # Imágenes de evidencias (excluidas de git)

migrations/         # Migraciones Doctrine
```

---

## Convenciones de commits

Formato: `type(scope): descripción en imperativo`

```
feat(pta): agregar filtro por departamento en listado
fix(reportes): corregir cálculo de totales trimestrales
refactor(usuarios): extraer validación a servicio separado
```

Tipos: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`, `perf`, `style`

---

## Ramas

| Rama | Propósito |
|---|---|
| `main` | Rama principal / producción |
| `pepe` | Desarrollo activo actual |

---

## Pendientes antes de producción

- [ ] Migraciones sin trackear de origen desconocido en rama `pepe` (`Version20260603041054` — tabla `nombramiento`, `Version20260604153852` — tabla `ciclo_indicadores`): verificar con el equipo a qué módulo pertenecen antes de mergear a `main`
- [ ] Solicitud de gastos: implementar Actividad 2 (notificaciones / generación de documento)
- [ ] Solicitud de gastos: confirmar con finanzas el criterio de numeración de Serie/Folio del PDF exportado
- [ ] Reporte de indicadores: `formula_opcional` en edit no pre-carga el valor guardado originalmente
- [ ] Ejecutar migraciones de Doctrine pendientes en el entorno de despliegue (`doctrine:migrations:migrate`)

---

## Lo que falta en este README

> Secciones que requieren información adicional del equipo:

- Instrucciones de instalación detalladas (versiones exactas, comandos de fixtures)
- Variables de entorno requeridas (`.env` keys)
- Cómo correr las pruebas (`phpunit`)
- Cómo generar una nueva migración
- Descripción del sistema de autenticación y roles Symfony
- URL del entorno de staging/producción
- Capturas de pantalla de la UI principal
