-- Catalogo base de novedades estudiantiles.

INSERT INTO tipo_novedad (tnonombre, tnodescripcion, tnogravedad)
VALUES
    ('No trae materiales', 'El estudiante no presenta los materiales requeridos para la clase.', 'LEVE'),
    ('No trabaja en clase', 'El estudiante no desarrolla las actividades asignadas durante la clase.', 'LEVE'),
    ('Consume alimentos o bebidas', 'Consume alimentos o bebidas en un momento no permitido.', 'LEVE'),
    ('Interrumpe la clase', 'Interrumpe el desarrollo normal de la clase.', 'MEDIA'),
    ('Uso indebido del celular', 'Usa celular u otro dispositivo sin autorizacion.', 'MEDIA'),
    ('Agresion verbal', 'Ofensa, insulto o agresion verbal hacia otro miembro de la comunidad.', 'GRAVE'),
    ('Agresion fisica', 'Contacto fisico agresivo hacia otro miembro de la comunidad.', 'GRAVE'),
    ('Otro', 'Novedad no clasificada en el catalogo principal.', 'MEDIA')
ON CONFLICT (tnonombre) DO NOTHING;
