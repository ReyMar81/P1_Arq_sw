# Guía: Arquitectura MVC en PHP

> **Materia:** Arquitectura de Software  
> **Docente:** Guía oficial de cátedra  
> **Patrón:** Modelo - Vista - Controlador (MVC)

---

## 1. ¿Qué es MVC?

**MVC (Modelo-Vista-Controlador)** es un patrón de arquitectura de software que separa los datos y la lógica de negocio de la interfaz de usuario en tres capas independientes.

| Capa | Responsabilidad |
|------|----------------|
| **Modelo** | Maneja los datos: accede, actualiza e interactúa con la base de datos (SELECT, INSERT, UPDATE, DELETE) |
| **Vista** | Contiene solo HTML + PHP mínimo para mostrar la salida de datos al usuario |
| **Controlador** | Actúa como enlace entre el Modelo y la Vista. Recibe la petición del usuario, solicita datos al modelo y los envía a la vista |

---

## 2. Flujo de una Petición en MVC

```
Usuario
   │
   ▼
Controlador  ──────────────►  Modelo  ──► Base de Datos
   │                            │
   │         ◄─── datos ────────┘
   │
   ▼
Vista (HTML con datos)
   │
   ▼
Respuesta al Usuario
```

### Pasos detallados:
1. El usuario realiza una petición (clic, formulario, URL).
2. El **controlador** captura la petición.
3. El **controlador** solicita los datos al **modelo**.
4. El **modelo** interactúa con la base de datos y devuelve la información.
5. El **controlador** recibe la información y se la pasa a la **vista**.
6. La **vista** muestra la información al usuario en forma de HTML.

---

## 3. Estructura de Carpetas

La estructura que se debe seguir es la siguiente:

```
proyecto/
├── controllers/
│   └── personas_controller.php
├── db/
│   └── db.php
├── models/
│   └── personas_model.php
├── views/
│   └── personas_view.phtml
└── index.php
```

### Descripción de cada carpeta:

| Carpeta / Archivo | Descripción |
|-------------------|-------------|
| `controllers/` | Contiene los scripts controladores de cada entidad |
| `db/` | Contiene únicamente el archivo de conexión a la BD |
| `models/` | Contiene las clases que acceden a la base de datos |
| `views/` | Contiene las vistas en formato `.phtml` |
| `index.php` | Punto de entrada único de la aplicación |

---

## 4. Convenciones de Nombres

| Tipo | Carpeta | Nombre de Archivo | Nombre de Clase |
|------|---------|-------------------|-----------------|
| Conexión | `db/` | `db.php` | `Conectar` |
| Modelo | `models/` | `<entidad>_model.php` | `<entidad>_model` |
| Controlador | `controllers/` | `<entidad>_controller.php` | *(no es clase, es script)* |
| Vista | `views/` | `<entidad>_view.phtml` | *(no aplica)* |

> **Nota:** Las vistas usan la extensión `.phtml` (PHP + HTML). El controlador **NO es una clase**, es un script que orquesta el modelo y la vista.

---

## 5. Implementación: Código de Ejemplo

### `db/db.php` — Conexión a la Base de Datos

```php
<?php
class Conectar {
    public static function conexion() {
        $con = mysqli_connect("localhost", "root", "", "base_datos");
        return $con;
    }
}
?>
```

**Puntos clave:**
- Clase `Conectar` con método **estático** `conexion()`.
- Usa `mysqli_connect()` para conectarse.
- Retorna el objeto de conexión.

---

### `models/personas_model.php` — Modelo

```php
<?php
class personas_model {
    private $con;

    public function __construct() {
        $this->con = Conectar::conexion();
    }

    public function get_personas() {
        $sql = "SELECT * FROM personas";
        $resultado = mysqli_query($this->con, $sql);
        $personas = [];
        while ($fila = mysqli_fetch_array($resultado)) {
            $personas[] = $fila;
        }
        return $personas;
    }

    public function insertar_persona($nombre, $apellido) {
        $sql = "INSERT INTO personas (nombre, apellido) VALUES ('$nombre', '$apellido')";
        return mysqli_query($this->con, $sql);
    }

    public function actualizar_persona($id, $nombre, $apellido) {
        $sql = "UPDATE personas SET nombre='$nombre', apellido='$apellido' WHERE id=$id";
        return mysqli_query($this->con, $sql);
    }

    public function eliminar_persona($id) {
        $sql = "DELETE FROM personas WHERE id=$id";
        return mysqli_query($this->con, $sql);
    }
}
?>
```

**Puntos clave:**
- La clase recibe los **datos como parámetros** en cada método (sin getters/setters).
- El constructor establece la conexión usando `Conectar::conexion()`.
- Los métodos hacen las consultas y **retornan** los resultados o un booleano.

---

### `controllers/personas_controller.php` — Controlador

```php
<?php
require_once '../db/db.php';
require_once '../models/personas_model.php';

// 1. Llamar al modelo
$modelo = new personas_model();
$personas = $modelo->get_personas();

// 2. Cargar la vista
require_once '../views/personas_view.phtml';
?>
```

**⚠️ Regla crítica:**
> El controlador **SIEMPRE** sigue este orden:
> 1. **Primero:** instanciar el modelo y obtener datos.
> 2. **Después:** hacer `require_once` de la vista.

---

### `views/personas_view.phtml` — Vista

```php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Personas</title>
</head>
<body>
    <h1>Lista de Personas</h1>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($personas as $persona): ?>
            <tr>
                <td><?php echo $persona['id']; ?></td>
                <td><?php echo $persona['nombre']; ?></td>
                <td><?php echo $persona['apellido']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
```

**Puntos clave:**
- La vista **solo muestra datos**, no contiene lógica de negocio.
- Usa `foreach` para iterar sobre los datos que vienen del controlador.
- La extensión es `.phtml`.

---

## 6. Resumen Visual de Responsabilidades

```
┌─────────────────────────────────────────────────────────┐
│                    index.php                            │
│              (Punto de entrada único)                   │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│              CONTROLADOR (script)                       │
│  • require_once '../db/db.php'                          │
│  • require_once '../models/<entidad>_model.php'         │
│  • $modelo = new <entidad>_model();                     │
│  • $datos = $modelo->get_<entidad>s();                  │
│  • require_once '../views/<entidad>_view.phtml';        │
└─────────────────────────────────────────────────────────┘
        │                              │
        ▼                              ▼
┌──────────────────┐        ┌──────────────────────────┐
│     MODELO       │        │          VISTA           │
│ class <entidad>  │        │  Solo HTML + foreach     │
│ _model {         │        │  para mostrar $datos     │
│   get_()         │        │  Extensión: .phtml       │
│   insertar_()    │        └──────────────────────────┘
│   actualizar_()  │
│   eliminar_()    │
│ }                │
└──────────────────┘
        │
        ▼
┌──────────────────┐
│   db/db.php      │
│ class Conectar { │
│   conexion()     │
│ }                │
└──────────────────┘
        │
        ▼
   Base de Datos
```

---

## 7. Checklist de Implementación

Antes de entregar o presentar, verificar:

- [ ] La carpeta `db/` existe con `db.php` y la clase `Conectar`.
- [ ] Los modelos están en `models/` con nombre `<entidad>_model.php`.
- [ ] Los controladores están en `controllers/` con nombre `<entidad>_controller.php`.
- [ ] Las vistas están en `views/` con extensión `.phtml`.
- [ ] El controlador **primero** llama al modelo y **después** carga la vista.
- [ ] Las vistas **no contienen** lógica de negocio ni consultas SQL.
- [ ] Los modelos **no contienen** HTML.
- [ ] Existe un `index.php` como punto de entrada.

---

## 8. Errores Comunes a Evitar

| ❌ Error | ✅ Corrección |
|---------|--------------|
| Poner SQL directo en la vista | El SQL solo va en el Modelo |
| Hacer el controlador una clase | El controlador es un script directo |
| Usar extensión `.php` en vistas | Las vistas usan `.phtml` |
| Cargar la vista antes del modelo | Siempre: modelo primero, vista después |
| Mezclar HTML con lógica de negocio | Separar estrictamente cada capa |
| Conexión directa en el modelo sin `db.php` | Siempre usar `Conectar::conexion()` |
