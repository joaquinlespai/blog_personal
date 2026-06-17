<?php
declare(strict_types=1);

$dataDir = __DIR__ . '/data';
$dbPath = $dataDir . '/farmacia.sqlite';
$isNewDatabase = !file_exists($dbPath);

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("
    CREATE TABLE IF NOT EXISTS medicamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        laboratorio TEXT NOT NULL,
        categoria TEXT NOT NULL,
        precio REAL NOT NULL CHECK (precio >= 0),
        stock INTEGER NOT NULL CHECK (stock >= 0),
        fecha_vencimiento TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
");

if ($isNewDatabase) {
    $seed = $pdo->prepare("
        INSERT INTO medicamentos (nombre, laboratorio, categoria, precio, stock, fecha_vencimiento)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $seed->execute(['Paracetamol 500 mg', 'Laboratorio Chile', 'Analgesico', 1990, 45, '2027-04-15']);
    $seed->execute(['Ibuprofeno 400 mg', 'PharmaVida', 'Antiinflamatorio', 2490, 30, '2027-08-20']);
    $seed->execute(['Loratadina 10 mg', 'MediSur', 'Antialergico', 3290, 22, '2028-01-10']);
}

function cleanText(string $value): string
{
    return trim($value);
}

function money(float $value): string
{
    return '$' . number_format($value, 0, ',', '.');
}

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $delete = $pdo->prepare('DELETE FROM medicamentos WHERE id = ?');
            $delete->execute([$id]);
            header('Location: index.php?msg=deleted');
            exit;
        }
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = cleanText($_POST['nombre'] ?? '');
        $laboratorio = cleanText($_POST['laboratorio'] ?? '');
        $categoria = cleanText($_POST['categoria'] ?? '');
        $precio = (float) ($_POST['precio'] ?? -1);
        $stock = (int) ($_POST['stock'] ?? -1);
        $fechaVencimiento = cleanText($_POST['fecha_vencimiento'] ?? '');

        if ($nombre === '') {
            $errors[] = 'El nombre del medicamento es obligatorio.';
        }
        if ($laboratorio === '') {
            $errors[] = 'El laboratorio es obligatorio.';
        }
        if ($categoria === '') {
            $errors[] = 'La categoria es obligatoria.';
        }
        if ($precio < 0) {
            $errors[] = 'El precio debe ser mayor o igual a 0.';
        }
        if ($stock < 0) {
            $errors[] = 'El stock debe ser mayor o igual a 0.';
        }
        if ($fechaVencimiento === '') {
            $errors[] = 'La fecha de vencimiento es obligatoria.';
        }

        if (!$errors) {
            if ($id > 0) {
                $update = $pdo->prepare("
                    UPDATE medicamentos
                    SET nombre = ?, laboratorio = ?, categoria = ?, precio = ?, stock = ?, fecha_vencimiento = ?
                    WHERE id = ?
                ");
                $update->execute([$nombre, $laboratorio, $categoria, $precio, $stock, $fechaVencimiento, $id]);
                header('Location: index.php?msg=updated');
                exit;
            }

            $insert = $pdo->prepare("
                INSERT INTO medicamentos (nombre, laboratorio, categoria, precio, stock, fecha_vencimiento)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([$nombre, $laboratorio, $categoria, $precio, $stock, $fechaVencimiento]);
            header('Location: index.php?msg=created');
            exit;
        }
    }
}

$messages = [
    'created' => 'Medicamento creado correctamente.',
    'updated' => 'Medicamento modificado correctamente.',
    'deleted' => 'Medicamento eliminado correctamente.',
];
$message = $messages[$_GET['msg'] ?? ''] ?? '';

$editing = null;
if (isset($_GET['edit'])) {
    $find = $pdo->prepare('SELECT * FROM medicamentos WHERE id = ?');
    $find->execute([(int) $_GET['edit']]);
    $editing = $find->fetch(PDO::FETCH_ASSOC) ?: null;
}

$medicamentos = $pdo
    ->query('SELECT * FROM medicamentos ORDER BY id DESC')
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In-Fin Pharmacy - CRUD de Medicamentos</title>
    <style>
        :root {
            --blue: #004080;
            --blue-light: #0b6db8;
            --green: #168a57;
            --red: #c43b3b;
            --ink: #1f2937;
            --muted: #64748b;
            --line: #d9e2ec;
            --surface: rgba(255, 255, 255, 0.94);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--ink);
            background:
                linear-gradient(rgba(247, 250, 252, 0.84), rgba(232, 242, 250, 0.88)),
                url('farmacia-fondo.png') center / cover fixed;
        }

        header {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 12px 28px;
            background: var(--blue);
            color: white;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.18);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 22px;
            font-weight: 700;
        }

        .brand img {
            width: 52px;
            height: 52px;
            object-fit: contain;
            background: white;
            border-radius: 8px;
            padding: 4px;
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 6px;
            font-size: 14px;
        }

        nav a:hover {
            background: rgba(255, 255, 255, 0.16);
        }

        main {
            width: min(1180px, calc(100% - 32px));
            margin: 28px auto 48px;
        }

        section {
            margin-bottom: 24px;
            padding: 24px;
            background: var(--surface);
            border: 1px solid rgba(217, 226, 236, 0.9);
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }

        h1, h2, h3 {
            margin: 0 0 12px;
            line-height: 1.2;
        }

        h1 {
            font-size: 34px;
        }

        h2 {
            font-size: 23px;
            color: var(--blue);
        }

        p {
            margin: 0 0 12px;
            color: #334155;
            line-height: 1.55;
        }

        ul {
            margin: 0;
            padding-left: 20px;
            line-height: 1.6;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 24px;
            align-items: center;
        }

        .pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            border: 1px solid #b6d9f2;
            border-radius: 999px;
            padding: 7px 11px;
            background: #edf8ff;
            color: #0f4f7a;
            font-size: 13px;
            font-weight: 700;
        }

        .mockup {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 8px;
            display: block;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .crud-item {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 16px;
            background: #f8fafc;
        }

        .crud-item strong {
            display: block;
            color: var(--blue);
            margin-bottom: 6px;
        }

        .app-grid {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 20px;
            align-items: start;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #334155;
            font-size: 14px;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: 11px 12px;
            margin-bottom: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font: inherit;
        }

        input:focus {
            outline: 3px solid rgba(11, 109, 184, 0.18);
            border-color: var(--blue-light);
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        button,
        .button {
            border: 0;
            border-radius: 6px;
            padding: 10px 12px;
            background: var(--blue-light);
            color: white;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
        }

        button:hover,
        .button:hover {
            filter: brightness(0.95);
        }

        .button.secondary {
            background: #475569;
        }

        .button.danger,
        button.danger {
            background: var(--red);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 8px;
            background: white;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #e9f4fb;
            color: #123f63;
            font-size: 13px;
            text-transform: uppercase;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .notice {
            padding: 12px 14px;
            margin-bottom: 16px;
            border-radius: 6px;
            background: #e7f8ef;
            color: #10633d;
            border: 1px solid #a8e3c3;
            font-weight: 700;
        }

        .errors {
            padding: 12px 14px;
            margin-bottom: 16px;
            border-radius: 6px;
            background: #fff1f1;
            color: #922929;
            border: 1px solid #f2b7b7;
        }

        .inline-form {
            display: inline;
        }

        footer {
            padding: 24px;
            background: var(--blue);
            color: white;
            text-align: center;
        }

        footer p {
            margin: 0;
            color: white;
        }

        @media (max-width: 900px) {
            header,
            .hero,
            .app-grid {
                grid-template-columns: 1fr;
            }

            header {
                align-items: flex-start;
                flex-direction: column;
            }

            .grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
            main {
                width: min(100% - 20px, 1180px);
            }

            section {
                padding: 18px;
            }

            h1 {
                font-size: 28px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="brand">
            <img src="logo-infin-pharmacy1.png" alt="Logo de In-Fin Pharmacy">
            <span>In-Fin Pharmacy</span>
        </div>
        <nav aria-label="Menu principal">
            <a href="#inicio">Inicio</a>
            <a href="#crud">CRUD</a>
            <a href="#mockup">Mockup</a>
            <a href="#base-datos">Base de datos</a>
            <a href="#medicamentos">Medicamentos</a>
        </nav>
    </header>

    <main>
        <section class="hero" id="inicio">
            <div>
                <h1>Sistema de gestion de medicamentos</h1>
                <p>
                    In-Fin Pharmacy es una aplicacion web para administrar el inventario de medicamentos de una farmacia.
                    El sistema permite registrar productos, revisar existencias, actualizar datos comerciales y eliminar
                    registros que ya no correspondan al catalogo activo.
                </p>
                <p>
                    La aplicacion esta construida con PHP y SQLite. La base de datos se crea automaticamente dentro del
                    proyecto al abrir la pagina en Github Codespaces.
                </p>
                <h2>Integrantes</h2>
                <ul>
                    <li>Tomás Teihuel</li>
                    <li>Gerardo Ceron</li>
                    <li>Joaquin Lespai</li>
                </ul>
                <div class="pill-row">
                    <span class="pill">PHP</span>
                    <span class="pill">SQLite</span>
                    <span class="pill">CRUD</span>
                    <span class="pill">Github Codespaces</span>
                </div>
            </div>
            <img class="mockup" src="mockup.png" alt="Mockup de la interfaz principal de In-Fin Pharmacy">
        </section>

        <section id="crud">
            <h2>Descripcion de operaciones CRUD</h2>
            <div class="grid">
                <div class="crud-item">
                    <strong>Crear</strong>
                    Registrar un medicamento nuevo con nombre, laboratorio, categoria, precio, stock y fecha de vencimiento.
                </div>
                <div class="crud-item">
                    <strong>Leer</strong>
                    Mostrar en una tabla todos los medicamentos almacenados en la base de datos.
                </div>
                <div class="crud-item">
                    <strong>Modificar</strong>
                    Cargar los datos de un medicamento existente en el formulario y guardar sus cambios.
                </div>
                <div class="crud-item">
                    <strong>Eliminar</strong>
                    Borrar un medicamento del inventario cuando ya no forma parte del catalogo.
                </div>
            </div>
        </section>

        <section id="mockup">
            <h2>Mockup de interfaz principal</h2>
            <p>
                Esta imagen representa la pantalla esperada para el modulo principal de gestion de medicamentos.
            </p>
            <img class="mockup" src="mockup.png" alt="Mockup del CRUD de medicamentos">
        </section>

        <section id="base-datos">
            <h2>Base de datos</h2>
            <p>
                La base de datos seleccionada es SQLite. Se utiliza la tabla <strong>medicamentos</strong>, definida
                en el archivo <strong>schema.sql</strong>. Cuando la pagina se ejecuta por primera vez, PHP crea el
                archivo <strong>data/farmacia.sqlite</strong> y agrega algunos registros iniciales para demostrar el CRUD.
            </p>
        </section>

        <section id="medicamentos">
            <h2>CRUD funcional de medicamentos</h2>

            <?php if ($message): ?>
                <div class="notice"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="errors">
                    <strong>Revisa los siguientes datos:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="app-grid">
                <form method="post">
                    <h3><?php echo $editing ? 'Modificar medicamento' : 'Nuevo medicamento'; ?></h3>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($editing['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" required value="<?php echo htmlspecialchars((string) ($editing['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label for="laboratorio">Laboratorio</label>
                    <input id="laboratorio" name="laboratorio" required value="<?php echo htmlspecialchars((string) ($editing['laboratorio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label for="categoria">Categoria</label>
                    <input id="categoria" name="categoria" required value="<?php echo htmlspecialchars((string) ($editing['categoria'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label for="precio">Precio</label>
                    <input id="precio" name="precio" type="number" min="0" step="1" required value="<?php echo htmlspecialchars((string) ($editing['precio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label for="stock">Stock</label>
                    <input id="stock" name="stock" type="number" min="0" step="1" required value="<?php echo htmlspecialchars((string) ($editing['stock'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label for="fecha_vencimiento">Fecha de vencimiento</label>
                    <input id="fecha_vencimiento" name="fecha_vencimiento" type="date" required value="<?php echo htmlspecialchars((string) ($editing['fecha_vencimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="actions">
                        <button type="submit"><?php echo $editing ? 'Guardar cambios' : 'Crear medicamento'; ?></button>
                        <?php if ($editing): ?>
                            <a class="button secondary" href="index.php#medicamentos">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div>
                    <h3>Listado de medicamentos</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Laboratorio</th>
                                <th>Categoria</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Vence</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicamentos as $medicamento): ?>
                                <tr>
                                    <td><?php echo (int) $medicamento['id']; ?></td>
                                    <td><?php echo htmlspecialchars($medicamento['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($medicamento['laboratorio'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($medicamento['categoria'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo money((float) $medicamento['precio']); ?></td>
                                    <td><?php echo (int) $medicamento['stock']; ?></td>
                                    <td><?php echo htmlspecialchars($medicamento['fecha_vencimiento'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a class="button" href="index.php?edit=<?php echo (int) $medicamento['id']; ?>#medicamentos">Editar</a>
                                            <form class="inline-form" method="post" onsubmit="return confirm('Desea eliminar este medicamento?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int) $medicamento['id']; ?>">
                                                <button class="danger" type="submit">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 In-Fin Pharmacy. Proyecto academico CRUD con PHP y SQLite.</p>
    </footer>
</body>
</html>
