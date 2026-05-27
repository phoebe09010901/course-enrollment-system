ALTER TABLE course_projects ADD proposal_batch_id VARCHAR(64) NULL;
ALTER TABLE course_projects ADD template_processing_started_at DATETIME NULL;
ALTER TABLE course_projects ADD template_processing_by VARCHAR(120) NULL;
ALTER TABLE course_projects ADD worker_run_id VARCHAR(120) NULL;
ALTER TABLE course_projects ADD template_error_code VARCHAR(80) NULL;
ALTER TABLE course_projects ADD template_error_message TEXT NULL;
ALTER TABLE course_projects ADD KEY idx_course_projects_worker_run_id (worker_run_id);
ALTER TABLE course_projects ADD KEY idx_course_projects_proposal_batch_id (proposal_batch_id);

UPDATE course_projects
SET proposal_batch_id = CONCAT('batch_', SUBSTRING(SHA1(CONCAT(project_id, '-', id, '-', created_at)), 1, 24))
WHERE proposal_batch_id IS NULL OR proposal_batch_id = '';

UPDATE course_projects
SET template_status = 'pending_template'
WHERE template_status IN ('pending_canva_proposals', 'chat_a_trigger_queued', 'chat_a_triggered');

UPDATE course_projects
SET template_status = 'template_ready'
WHERE template_status = 'canva_proposals_ready';

UPDATE course_projects
SET template_status = 'template_ready'
WHERE template_status = 'canva_template_selected';

ALTER TABLE template_proposals ADD proposal_batch_id VARCHAR(64) NULL AFTER project_id;
UPDATE template_proposals tp
INNER JOIN course_projects cp ON cp.project_id = tp.project_id
SET tp.proposal_batch_id = COALESCE(cp.proposal_batch_id, CONCAT(cp.project_id, '-initial'));
ALTER TABLE template_proposals DROP KEY uq_template_proposals_project_proposal;
ALTER TABLE template_proposals ADD UNIQUE KEY uq_template_proposals_project_batch_code (project_id, proposal_batch_id, proposal_code);
ALTER TABLE template_proposals ADD KEY idx_template_proposals_batch_id (proposal_batch_id);
