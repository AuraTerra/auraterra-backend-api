# AuraTerra
cambio en la compu de tamara
# AuraTerra

## 📌 Descripción General del Proyecto
AuraTerra es una plataforma de software orientada a servicios (API-First) diseñada para transformar datos climáticos satelitales en tiempo real en directivas operativas concretas para dos sectores productivos clave:

- **Productores y contratistas agropecuarios**: Cálculo de ventanas seguras de labor, fertilización y alertas de pulverización fitosanitaria bajo el marco legal provincial.
- **Logística de eventos al aire libre (AuraEvents)**: Estimación de curvas térmicas, alertas de condensación/rocío, rigidez de sujeción por viento para carpas y cálculo de la "hora dorada" lumínica.

### Arquitectura
El sistema está desacoplado en tres repositorios independientes:
- **Backend API REST**: PHP 8+ con Eloquent ORM, MySQL (InnoDB), proxy de red cURL y políticas CORS.
- **Frontend Web Dashboard**: JavaScript Vanilla ES6+ y CSS responsive.
- **Frontend Mobile**: Aplicación táctil optimizada para teléfonos móviles y trabajo en campo.

---

## 📌 Declaración del Problema
Los productores agrícolas y organizadores de eventos dependen de aplicaciones meteorológicas de consumo masivo, lo que genera tres problemas:

1. **Incumplimiento legal y deriva ambiental**: La Ley Provincial de Plaguicidas restringe la aplicación de fitosanitarios a velocidades de viento entre 7 y 15 km/h. Las apps comunes no calculan si la aplicación está habilitada o suspendida por ley.
2. **Falta de criterio agronómico situado**: No ofrecen recomendaciones específicas para el suelo (ej. siembra de trigo de ciclo largo).
3. **Falta de resiliencia y trazabilidad**: Muchas apps locales colapsan ante peticiones masivas y carecen de auditoría sobre los operadores.

---

## 📌 Objetivos del Proyecto
### Objetivo General
Desarrollar una solución informática distribuida (Web, Mobile y API) que procese variables meteorológicas en tiempo real para respaldar decisiones operativas seguras, legales y eficientes.

### Objetivos Específicos
- **Centralización API**: Endpoints REST (`/clima/actual`, `/clima/pronostico`, `/registrar_click`) con respuestas JSON < 1.5 segundos.
- **Control Normativo Automatizado**: Clasificación algorítmica de pulverización como "Permitida" o "Suspendida".
- **Control de Acceso por Roles (RBAC)**: Vistas filtradas según rol (agricultor, planificador).
- **Seguridad y Telemetría**: Rate Limiter + interceptor anti-ráfaga (HTTP 429).
- **Multiplataforma**: Web y Mobile consumen la misma API.

---

## 📌 Definición del Alcance
| Área         | Incluido (In-Scope) | Excluido (Out-of-Scope) |
|--------------|---------------------|--------------------------|
| **Normativa** | Validación de umbrales legales de viento | Emisión de recetas agronómicas digitales |
| **Meteorología** | Consulta en tiempo real vía OpenWeatherMap | Instalación de estaciones físicas |
| **Usuarios** | Roles diferenciados con sesiones protegidas | Pasarelas de pago electrónico |
| **Arquitectura** | 3 repositorios Git desacoplados | Sincronización offline sin Internet |
| **Auditoría** | Telemetría de clics y corte por ráfaga | Monitoreo satelital en vivo |

---

## 📌 Partes Interesadas (Stakeholders)
- **Productores Agropecuarios y Aplicadores**: Verificación de ventanas seguras de labor.
- **Coordinadores Logísticos de Eventos**: Anticipar comportamiento climático sobre estructuras.
- **Equipo de Desarrollo (Folmer, Gareis, Godoy)**: Diseño, implementación y QA.
- **Cátedra y Docentes Evaluadores (UTN)**: Evaluación técnica y gestión del proyecto.

---

## 📌 Suposiciones y Restricciones
### Suposiciones
- API externa OpenWeatherMap con disponibilidad ≥ 99%.
- Operadores con conectividad básica (datos móviles/WiFi).
- Usuarios otorgan permiso de ubicación al navegador.
- Navegadores cumplen ECMAScript 6+ y Fetch API.

### Restricciones
- **Tecnológicas**: Backend en PHP 8+, Apache, MySQL InnoDB.
- **Temporales**: Proyecto dentro del ciclo lectivo 2026.
- **Equipo**: 3 estudiantes.
- **Normativas**: Algoritmo fitosanitario ajustado a límites legales.

---

## 📌 Criterios de Aceptación
- **Corrección Funcional**: Clasificación legal de viento (7–15 km/h = Permitido).
- **Rendimiento**: Endpoints responden < 1.5 segundos.
- **Seguridad Perimetral**: 3 clics en < 3 segundos → suspensión (HTTP 429).
- **Usabilidad**: Alternar búsqueda manual y GPS en un clic.
- **Compatibilidad**: Navegadores modernos + diseño responsive.
- **MVP**: Autenticación segura, consumo API vía cURL, telemetría persistida y repositorios Git independientes.
