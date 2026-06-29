ALTER TABLE comunicado_entrega
    DROP CONSTRAINT IF EXISTS ck_comunicado_entrega_canal;

ALTER TABLE comunicado_entrega
    ADD CONSTRAINT ck_comunicado_entrega_canal
    CHECK (cencanal IN ('SISTEMA', 'EMAIL', 'WHATSAPP'));

DROP INDEX IF EXISTS idx_comunicado_entrega_email_estado;

CREATE INDEX IF NOT EXISTS idx_comunicado_entrega_canal_estado
    ON comunicado_entrega (cencanal, cenestado, cenfecha_programada);
