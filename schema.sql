CREATE TABLE IF NOT EXISTS medicamentos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    laboratorio TEXT NOT NULL,
    categoria TEXT NOT NULL,
    precio REAL NOT NULL CHECK (precio >= 0),
    stock INTEGER NOT NULL CHECK (stock >= 0),
    fecha_vencimiento TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO medicamentos (nombre, laboratorio, categoria, precio, stock, fecha_vencimiento)
VALUES
    ('Paracetamol 500 mg', 'Laboratorio Chile', 'Analgesico', 1990, 45, '2027-04-15'),
    ('Ibuprofeno 400 mg', 'PharmaVida', 'Antiinflamatorio', 2490, 30, '2027-08-20'),
    ('Loratadina 10 mg', 'MediSur', 'Antialergico', 3290, 22, '2028-01-10');
