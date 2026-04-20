# Casos de Uso del Sistema de Control de Asistencia mediante QR

## Actores del sistema
- Docente
- Estudiante

---

## CU1: Gestionar Materias

**Propósito**  
Gestionar materias.

**Descripción**  
Permite al docente gestionar las materias que imparte.

**Actores**  
Docente

**Actor Iniciador**  
Docente

**Precondición**  
Ninguna.

**Proceso**  

### 1. Crear materia
- Insertar datos.
- Crear materia.

### 2. Actualizar materia
- Identificar la materia.
- Modificar datos.
- Guardar datos.

### 3. Eliminar materia
- Identificar la materia.
- Eliminar materia.

**Postcondición**  
Se crea, actualiza o elimina el registro de materia.

**Excepciones**  
- La materia ya existe en el sistema.

---

## CU2: Gestionar Grupos

**Propósito**  
Gestionar grupos.

**Descripción**  
Permite al docente gestionar los grupos asociados a cada materia que imparte.

**Actores**  
Docente

**Actor Iniciador**  
Docente

**Precondición**  
Tener al menos una materia registrada.

**Proceso**  

### 1. Crear grupo
- Insertar datos.
- Seleccionar materia.
- Crear grupo.

### 2. Actualizar grupo
- Identificar el grupo.
- Modificar datos.
- Guardar datos.

### 3. Eliminar grupo
- Identificar el grupo.
- Eliminar grupo.

**Postcondición**  
Se crea, actualiza o elimina el registro de grupo.

**Excepciones**  
- El grupo ya existe en la materia seleccionada.

---

## CU3: Registrar estudiantes en un grupo

**Propósito**  
Registrar estudiantes a un grupo.

**Descripción**  
Permite al docente registrar estudiantes en un grupo.

**Actores**  
Docente

**Actor Iniciador**  
Docente

**Precondición**  
Tener al menos una materia y un grupo creados.

**Proceso**  

### 1. Registrar estudiantes a un grupo
- Identificar el grupo.
- Seleccionar archivo Excel/CSV.
- Mostrar la lista de estudiantes cargados.
- Agregar o eliminar manualmente un estudiante, si es necesario.
- Confirmar y guardar el registro de estudiantes en el grupo.

**Postcondición**  
Los estudiantes quedan registrados y asociados al grupo seleccionado.

**Excepciones**  
- El archivo no tiene un formato válido.
- Existen datos incompletos o inválidos en el archivo.
- El grupo no existe en el sistema.
- Un estudiante ya se encuentra registrado en el grupo.
- No existen estudiantes válidos para registrar.

---

## CU4: Gestionar Clases

**Propósito**  
Gestionar clases.

**Descripción**  
Permite al docente gestionar clases dentro de un grupo.

**Actores**  
Docente

**Actor Iniciador**  
Docente

**Precondición**  
Tener al menos una materia y un grupo creados.

**Proceso**  

### 1. Crear clase
- Identificar el grupo.
- Insertar datos.
- Crear clase.

### 2. Actualizar clase
- Identificar y seleccionar la clase.
- Modificar datos.
- Guardar cambios.

### 3. Eliminar clase
- Identificar y seleccionar la clase.
- Eliminar la clase.

**Postcondición**  
Se crea, actualiza o elimina el registro de clase.

**Excepciones**  
- El grupo no existe en el sistema.
- La clase ya existe en el grupo seleccionado.

---

## CU5: Generar código QR para la clase

**Propósito**  
Generar código QR para la clase.

**Descripción**  
Permite al docente generar un código QR para una clase programada, para que los estudiantes registren su asistencia.

**Actores**  
Docente

**Actor Iniciador**  
Docente

**Precondición**  
Tener al menos una clase registrada.

**Proceso**  

### 1. Generar código QR para la clase
- Identificar la clase.
- Validar fecha y horario de la clase.
- Generar código QR.
- Mostrar código QR.

**Postcondición**  
El código QR queda generado y disponible para el registro de asistencia.

**Excepciones**  
- Ninguna.

---

## CU6: Registrar asistencia mediante QR

**Propósito**  
Registrar asistencia mediante código QR.

**Descripción**  
Permite al estudiante identificarse en el sistema y escanear un código QR para registrar su asistencia en la clase correspondiente.

**Actores**  
Estudiante

**Actor Iniciador**  
Estudiante

**Precondición**  
Tener registro en el sistema.

**Proceso**  

### 1. Registrar asistencia mediante QR
- Identificarse en el sistema.
- Escanear código QR.
- Validar código QR.
- Registrar asistencia.

**Postcondición**  
La asistencia del estudiante queda registrada.

**Excepciones**  
- El estudiante no existe en el sistema.
- El código QR no es válido.
- El código QR ha expirado.
- La asistencia ya fue registrada anteriormente.