ALTER TABLE course_projects ADD selection_token CHAR(64) NULL;
ALTER TABLE course_projects ADD UNIQUE KEY uq_course_projects_selection_token (selection_token);
