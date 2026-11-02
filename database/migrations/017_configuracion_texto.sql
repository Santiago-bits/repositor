-- Las claves de los avisos push son más largas que 255 caracteres.
ALTER TABLE configuracion MODIFY valor TEXT NOT NULL;
