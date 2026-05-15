-- Datos base de plantillas para calificaciones.
-- Puede ejecutarse varias veces sin duplicar registros.

INSERT INTO plantilla_calificacion (
    pclnombre,
    pcldescripcion,
    pcltipo_base,
    pclminima,
    pclmaxima,
    pclaprobacion,
    pcldecimales,
    pclmetodo_decimal,
    pclpromedia_final,
    pclaplica_promocion,
    pclestado
)
VALUES
    ('Inicial', 'Plantilla cualitativa por ambitos y destrezas para Educacion Inicial.', 'AMBITOS_DESTREZAS', NULL, NULL, NULL, 0, 'REDONDEO', false, false, true),
    ('Preparatoria', 'Plantilla cualitativa por ambitos y destrezas para Preparatoria.', 'AMBITOS_DESTREZAS', NULL, NULL, NULL, 0, 'REDONDEO', false, false, true),
    ('Basica Elemental', 'Plantilla cuantitativa simple con equivalencia cualitativa y sin perdida de anio.', 'CUANTITATIVO', 0.00, 10.00, 7.00, 2, 'REDONDEO', true, false, true),
    ('Basica Media Trimestral', 'Plantilla cuantitativa trimestral con componentes formativo y sumativo.', 'CUANTITATIVO', 0.00, 10.00, 7.00, 2, 'REDONDEO', true, false, true),
    ('Basica Superior Trimestral', 'Plantilla cuantitativa trimestral con promocion e instancias extraordinarias.', 'CUANTITATIVO', 0.00, 10.00, 7.00, 2, 'REDONDEO', true, true, true),
    ('Bachillerato Trimestral', 'Plantilla cuantitativa trimestral para Bachillerato con promocion.', 'CUANTITATIVO', 0.00, 10.00, 7.00, 2, 'REDONDEO', true, true, true)
ON CONFLICT (pclnombre) DO UPDATE
SET pcldescripcion = EXCLUDED.pcldescripcion,
    pcltipo_base = EXCLUDED.pcltipo_base,
    pclminima = EXCLUDED.pclminima,
    pclmaxima = EXCLUDED.pclmaxima,
    pclaprobacion = EXCLUDED.pclaprobacion,
    pcldecimales = EXCLUDED.pcldecimales,
    pclmetodo_decimal = EXCLUDED.pclmetodo_decimal,
    pclpromedia_final = EXCLUDED.pclpromedia_final,
    pclaplica_promocion = EXCLUDED.pclaplica_promocion,
    pclestado = EXCLUDED.pclestado,
    pclfecha_modificacion = CURRENT_TIMESTAMP;

INSERT INTO plantilla_subperiodo (pclid, psunombre, psuorden, psuparticipa_final, psupeso_final)
SELECT p.pclid, source.psunombre, source.psuorden, true, NULL
FROM plantilla_calificacion p
INNER JOIN (
    VALUES
        ('Inicial', 'Trimestre 1', 1),
        ('Inicial', 'Trimestre 2', 2),
        ('Inicial', 'Trimestre 3', 3),
        ('Preparatoria', 'Trimestre 1', 1),
        ('Preparatoria', 'Trimestre 2', 2),
        ('Preparatoria', 'Trimestre 3', 3),
        ('Basica Elemental', 'Trimestre 1', 1),
        ('Basica Elemental', 'Trimestre 2', 2),
        ('Basica Elemental', 'Trimestre 3', 3),
        ('Basica Media Trimestral', 'Trimestre 1', 1),
        ('Basica Media Trimestral', 'Trimestre 2', 2),
        ('Basica Media Trimestral', 'Trimestre 3', 3),
        ('Basica Superior Trimestral', 'Trimestre 1', 1),
        ('Basica Superior Trimestral', 'Trimestre 2', 2),
        ('Basica Superior Trimestral', 'Trimestre 3', 3),
        ('Bachillerato Trimestral', 'Trimestre 1', 1),
        ('Bachillerato Trimestral', 'Trimestre 2', 2),
        ('Bachillerato Trimestral', 'Trimestre 3', 3)
) AS source (pclnombre, psunombre, psuorden) ON source.pclnombre = p.pclnombre
ON CONFLICT (pclid, psuorden) DO UPDATE
SET psunombre = EXCLUDED.psunombre,
    psuparticipa_final = EXCLUDED.psuparticipa_final,
    psupeso_final = EXCLUDED.psupeso_final;

INSERT INTO plantilla_componente (psuid, pconombre, pcoorden, pcopeso, pcotipo_calculo, pcoestado)
SELECT ps.psuid, source.pconombre, source.pcoorden, source.pcopeso, 'PROMEDIO_SIMPLE', true
FROM plantilla_calificacion p
INNER JOIN plantilla_subperiodo ps ON ps.pclid = p.pclid
INNER JOIN (
    VALUES
        ('Basica Elemental', 'Trimestre 1', 'Actividades', 1, NULL),
        ('Basica Elemental', 'Trimestre 2', 'Actividades', 1, NULL),
        ('Basica Elemental', 'Trimestre 3', 'Actividades', 1, NULL),
        ('Basica Media Trimestral', 'Trimestre 1', 'Evaluacion formativa', 1, 70.000),
        ('Basica Media Trimestral', 'Trimestre 1', 'Evaluacion sumativa', 2, 30.000),
        ('Basica Media Trimestral', 'Trimestre 2', 'Evaluacion formativa', 1, 70.000),
        ('Basica Media Trimestral', 'Trimestre 2', 'Evaluacion sumativa', 2, 30.000),
        ('Basica Media Trimestral', 'Trimestre 3', 'Evaluacion formativa', 1, 70.000),
        ('Basica Media Trimestral', 'Trimestre 3', 'Evaluacion sumativa', 2, 30.000),
        ('Basica Superior Trimestral', 'Trimestre 1', 'Evaluacion formativa', 1, 70.000),
        ('Basica Superior Trimestral', 'Trimestre 1', 'Evaluacion sumativa', 2, 30.000),
        ('Basica Superior Trimestral', 'Trimestre 2', 'Evaluacion formativa', 1, 70.000),
        ('Basica Superior Trimestral', 'Trimestre 2', 'Evaluacion sumativa', 2, 30.000),
        ('Basica Superior Trimestral', 'Trimestre 3', 'Evaluacion formativa', 1, 70.000),
        ('Basica Superior Trimestral', 'Trimestre 3', 'Evaluacion sumativa', 2, 30.000),
        ('Bachillerato Trimestral', 'Trimestre 1', 'Evaluacion formativa', 1, 70.000),
        ('Bachillerato Trimestral', 'Trimestre 1', 'Evaluacion sumativa', 2, 30.000),
        ('Bachillerato Trimestral', 'Trimestre 2', 'Evaluacion formativa', 1, 70.000),
        ('Bachillerato Trimestral', 'Trimestre 2', 'Evaluacion sumativa', 2, 30.000),
        ('Bachillerato Trimestral', 'Trimestre 3', 'Evaluacion formativa', 1, 70.000),
        ('Bachillerato Trimestral', 'Trimestre 3', 'Evaluacion sumativa', 2, 30.000)
) AS source (pclnombre, psunombre, pconombre, pcoorden, pcopeso)
    ON source.pclnombre = p.pclnombre
    AND source.psunombre = ps.psunombre
ON CONFLICT (psuid, pcoorden) DO UPDATE
SET pconombre = EXCLUDED.pconombre,
    pcopeso = EXCLUDED.pcopeso,
    pcotipo_calculo = EXCLUDED.pcotipo_calculo,
    pcoestado = EXCLUDED.pcoestado;

INSERT INTO plantilla_escala_cualitativa (
    pclid,
    peccodigo,
    pecnombre,
    pecdescripcion,
    pecvalor_minimo,
    pecvalor_maximo,
    pecorden,
    pecestado
)
SELECT
    p.pclid,
    source.peccodigo,
    source.pecnombre,
    source.pecdescripcion,
    source.pecvalor_minimo,
    source.pecvalor_maximo,
    source.pecorden,
    true
FROM plantilla_calificacion p
INNER JOIN (
    VALUES
        ('Inicial', 'I', 'Iniciado', 'Destreza en inicio.', NULL::numeric, NULL::numeric, 1),
        ('Inicial', 'EP', 'En proceso', 'Destreza en proceso de desarrollo.', NULL::numeric, NULL::numeric, 2),
        ('Inicial', 'A', 'Adquirido', 'Destreza adquirida.', NULL::numeric, NULL::numeric, 3),
        ('Preparatoria', 'I', 'Iniciado', 'Destreza en inicio.', NULL::numeric, NULL::numeric, 1),
        ('Preparatoria', 'EP', 'En proceso', 'Destreza en proceso de desarrollo.', NULL::numeric, NULL::numeric, 2),
        ('Preparatoria', 'A', 'Adquirido', 'Destreza adquirida.', NULL::numeric, NULL::numeric, 3),
        ('Basica Elemental', 'A+', 'Domina los aprendizajes', 'Alcanza de forma destacada los aprendizajes requeridos.', 9.01, 10.00, 1),
        ('Basica Elemental', 'A', 'Alcanza los aprendizajes', 'Alcanza los aprendizajes requeridos.', 8.01, 9.00, 2),
        ('Basica Elemental', 'B', 'Proximo a alcanzar', 'Esta proximo a alcanzar los aprendizajes requeridos.', 7.00, 8.00, 3),
        ('Basica Elemental', 'C', 'No alcanza', 'No alcanza los aprendizajes requeridos.', 0.00, 6.99, 4),
        ('Basica Media Trimestral', 'A+', 'Domina los aprendizajes', 'Alcanza de forma destacada los aprendizajes requeridos.', 9.01, 10.00, 1),
        ('Basica Media Trimestral', 'A', 'Alcanza los aprendizajes', 'Alcanza los aprendizajes requeridos.', 8.01, 9.00, 2),
        ('Basica Media Trimestral', 'B', 'Proximo a alcanzar', 'Esta proximo a alcanzar los aprendizajes requeridos.', 7.00, 8.00, 3),
        ('Basica Media Trimestral', 'C', 'No alcanza', 'No alcanza los aprendizajes requeridos.', 0.00, 6.99, 4),
        ('Basica Superior Trimestral', 'A+', 'Domina los aprendizajes', 'Alcanza de forma destacada los aprendizajes requeridos.', 9.01, 10.00, 1),
        ('Basica Superior Trimestral', 'A', 'Alcanza los aprendizajes', 'Alcanza los aprendizajes requeridos.', 8.01, 9.00, 2),
        ('Basica Superior Trimestral', 'B', 'Proximo a alcanzar', 'Esta proximo a alcanzar los aprendizajes requeridos.', 7.00, 8.00, 3),
        ('Basica Superior Trimestral', 'C', 'No alcanza', 'No alcanza los aprendizajes requeridos.', 0.00, 6.99, 4),
        ('Bachillerato Trimestral', 'A+', 'Domina los aprendizajes', 'Alcanza de forma destacada los aprendizajes requeridos.', 9.01, 10.00, 1),
        ('Bachillerato Trimestral', 'A', 'Alcanza los aprendizajes', 'Alcanza los aprendizajes requeridos.', 8.01, 9.00, 2),
        ('Bachillerato Trimestral', 'B', 'Proximo a alcanzar', 'Esta proximo a alcanzar los aprendizajes requeridos.', 7.00, 8.00, 3),
        ('Bachillerato Trimestral', 'C', 'No alcanza', 'No alcanza los aprendizajes requeridos.', 0.00, 6.99, 4)
) AS source (
    pclnombre,
    peccodigo,
    pecnombre,
    pecdescripcion,
    pecvalor_minimo,
    pecvalor_maximo,
    pecorden
) ON source.pclnombre = p.pclnombre
ON CONFLICT (pclid, peccodigo) DO UPDATE
SET pecnombre = EXCLUDED.pecnombre,
    pecdescripcion = EXCLUDED.pecdescripcion,
    pecvalor_minimo = EXCLUDED.pecvalor_minimo,
    pecvalor_maximo = EXCLUDED.pecvalor_maximo,
    pecorden = EXCLUDED.pecorden,
    pecestado = EXCLUDED.pecestado;

INSERT INTO plantilla_ambito (pclid, pambnombre, pambdescripcion, pamborden, pambestado)
SELECT p.pclid, source.pambnombre, source.pambdescripcion, source.pamborden, true
FROM plantilla_calificacion p
INNER JOIN (
    VALUES
        ('Inicial', 'Identidad y autonomia', 'Desarrollo de identidad, autonomia y cuidado personal.', 1),
        ('Inicial', 'Convivencia', 'Relaciones de convivencia y participacion con otros.', 2),
        ('Inicial', 'Relaciones logico-matematicas', 'Relaciones, nociones y pensamiento logico matematico.', 3),
        ('Inicial', 'Comprension y expresion del lenguaje', 'Comunicacion oral, comprension y expresion.', 4),
        ('Preparatoria', 'Identidad y autonomia', 'Desarrollo de identidad, autonomia y cuidado personal.', 1),
        ('Preparatoria', 'Convivencia', 'Relaciones de convivencia y participacion con otros.', 2),
        ('Preparatoria', 'Relaciones logico-matematicas', 'Relaciones, nociones y pensamiento logico matematico.', 3),
        ('Preparatoria', 'Comprension y expresion oral y escrita', 'Comunicacion oral, lectura y escritura inicial.', 4)
) AS source (pclnombre, pambnombre, pambdescripcion, pamborden) ON source.pclnombre = p.pclnombre
ON CONFLICT (pclid, pamborden) DO UPDATE
SET pambnombre = EXCLUDED.pambnombre,
    pambdescripcion = EXCLUDED.pambdescripcion,
    pambestado = EXCLUDED.pambestado;

INSERT INTO plantilla_destreza (pambid, pdescodigo, pdesnombre, pdesdescripcion, pdesorden, pdesestado)
SELECT a.pambid, source.pdescodigo, source.pdesnombre, source.pdesdescripcion, source.pdesorden, true
FROM plantilla_calificacion p
INNER JOIN plantilla_ambito a ON a.pclid = p.pclid
INNER JOIN (
    VALUES
        ('Inicial', 'Identidad y autonomia', 'IA-01', 'Reconoce datos personales y pertenencias.', 'Reconoce elementos basicos de identidad personal.', 1),
        ('Inicial', 'Identidad y autonomia', 'IA-02', 'Realiza actividades de cuidado personal.', 'Ejecuta rutinas sencillas de autonomia.', 2),
        ('Inicial', 'Convivencia', 'CO-01', 'Participa en juegos y actividades grupales.', 'Interactua respetando acuerdos basicos.', 1),
        ('Inicial', 'Convivencia', 'CO-02', 'Expresa emociones de forma adecuada.', 'Comunica emociones y necesidades.', 2),
        ('Inicial', 'Relaciones logico-matematicas', 'LM-01', 'Clasifica objetos por atributos.', 'Agrupa objetos por color, forma o tamano.', 1),
        ('Inicial', 'Relaciones logico-matematicas', 'LM-02', 'Reconoce nociones de cantidad.', 'Identifica cantidades simples en situaciones cotidianas.', 2),
        ('Inicial', 'Comprension y expresion del lenguaje', 'LE-01', 'Comprende instrucciones sencillas.', 'Sigue indicaciones orales de una o dos acciones.', 1),
        ('Inicial', 'Comprension y expresion del lenguaje', 'LE-02', 'Expresa ideas oralmente.', 'Comunica ideas, experiencias y necesidades.', 2),
        ('Preparatoria', 'Identidad y autonomia', 'IA-01', 'Actua con autonomia en rutinas escolares.', 'Organiza materiales y participa en rutinas.', 1),
        ('Preparatoria', 'Identidad y autonomia', 'IA-02', 'Reconoce normas de autocuidado.', 'Aplica habitos de higiene y seguridad.', 2),
        ('Preparatoria', 'Convivencia', 'CO-01', 'Respeta acuerdos de aula.', 'Participa siguiendo normas de convivencia.', 1),
        ('Preparatoria', 'Convivencia', 'CO-02', 'Colabora con sus companeros.', 'Trabaja con otros en actividades guiadas.', 2),
        ('Preparatoria', 'Relaciones logico-matematicas', 'LM-01', 'Ordena y clasifica elementos.', 'Usa criterios para ordenar y clasificar objetos.', 1),
        ('Preparatoria', 'Relaciones logico-matematicas', 'LM-02', 'Relaciona numeros con cantidades.', 'Asocia numeros iniciales con cantidades.', 2),
        ('Preparatoria', 'Comprension y expresion oral y escrita', 'LE-01', 'Comprende relatos e instrucciones.', 'Escucha y responde a mensajes orales.', 1),
        ('Preparatoria', 'Comprension y expresion oral y escrita', 'LE-02', 'Produce trazos y escrituras iniciales.', 'Explora escritura inicial segun su desarrollo.', 2)
) AS source (pclnombre, pambnombre, pdescodigo, pdesnombre, pdesdescripcion, pdesorden)
    ON source.pclnombre = p.pclnombre
    AND source.pambnombre = a.pambnombre
ON CONFLICT (pambid, pdesorden) DO UPDATE
SET pdescodigo = EXCLUDED.pdescodigo,
    pdesnombre = EXCLUDED.pdesnombre,
    pdesdescripcion = EXCLUDED.pdesdescripcion,
    pdesestado = EXCLUDED.pdesestado;

INSERT INTO plantilla_promocion_tramo (
    pclid,
    pptorden,
    pptnota_minima,
    pptnota_maxima,
    pptresultado,
    ppthabilita_extraordinaria,
    pptestado
)
SELECT
    p.pclid,
    source.pptorden,
    source.pptnota_minima,
    source.pptnota_maxima,
    source.pptresultado,
    source.ppthabilita_extraordinaria,
    true
FROM plantilla_calificacion p
INNER JOIN (
    VALUES
        ('Basica Superior Trimestral', 1, 7.00, 10.00, 'PROMOVIDO', false),
        ('Basica Superior Trimestral', 2, 4.00, 6.99, 'SUPLETORIO', true),
        ('Basica Superior Trimestral', 3, 0.00, 3.99, 'NO_PROMOVIDO', false),
        ('Bachillerato Trimestral', 1, 7.00, 10.00, 'PROMOVIDO', false),
        ('Bachillerato Trimestral', 2, 4.00, 6.99, 'SUPLETORIO', true),
        ('Bachillerato Trimestral', 3, 0.00, 3.99, 'NO_PROMOVIDO', false)
) AS source (
    pclnombre,
    pptorden,
    pptnota_minima,
    pptnota_maxima,
    pptresultado,
    ppthabilita_extraordinaria
) ON source.pclnombre = p.pclnombre
ON CONFLICT (pclid, pptorden) DO UPDATE
SET pptnota_minima = EXCLUDED.pptnota_minima,
    pptnota_maxima = EXCLUDED.pptnota_maxima,
    pptresultado = EXCLUDED.pptresultado,
    ppthabilita_extraordinaria = EXCLUDED.ppthabilita_extraordinaria,
    pptestado = EXCLUDED.pptestado;

INSERT INTO plantilla_instancia_extraordinaria (
    pclid,
    pienombre,
    pieorden,
    pieaplica_sobre,
    pienota_habilita_minima,
    pienota_habilita_maxima,
    pienota_minima_aprobar,
    pienota_final_aprobado,
    piepermite_siguiente,
    pieestado
)
SELECT
    p.pclid,
    'Supletorio',
    1,
    'PROMEDIO_GENERAL',
    4.00,
    6.99,
    7.00,
    7.00,
    false,
    true
FROM plantilla_calificacion p
WHERE p.pclnombre IN ('Basica Superior Trimestral', 'Bachillerato Trimestral')
ON CONFLICT (pclid, pieorden) DO UPDATE
SET pienombre = EXCLUDED.pienombre,
    pieaplica_sobre = EXCLUDED.pieaplica_sobre,
    pienota_habilita_minima = EXCLUDED.pienota_habilita_minima,
    pienota_habilita_maxima = EXCLUDED.pienota_habilita_maxima,
    pienota_minima_aprobar = EXCLUDED.pienota_minima_aprobar,
    pienota_final_aprobado = EXCLUDED.pienota_final_aprobado,
    piepermite_siguiente = EXCLUDED.piepermite_siguiente,
    pieestado = EXCLUDED.pieestado;
