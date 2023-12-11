insert into submission(accession) Select distinct `Accession` FROM `SRA_Accessions` WHERE `Status`='live' and type='SUBMISSION' and `Submission` not in (select accession from submission);

insert into study(accession,submission_accession) Select distinct `Accession`, `Submission` FROM `SRA_Accessions` WHERE `Status`='live' and type='STUDY' and `Accession` not in (select accession from study);

insert into experiment(accession,submission_accession) Select distinct `Accession`, `Submission` FROM `SRA_Accessions` WHERE `Status`='live' and type='EXPERIMENT' and `Accession` not in (select accession from experiment);

insert into run(accession,submission_accession) Select distinct `Accession`, `Submission` FROM `SRA_Accessions` WHERE `Status`='live' and type='RUN' and `Accession` not in (select accession from run);

insert into sample(accession,submission_accession) Select distinct `Accession`, `Submission` FROM `SRA_Accessions` WHERE `Status`='live' and type='SAMPLE' and `Accession` not in (select accession from sample);

-- UPDATE `fastq` SET `accession` = REPLACE(REPLACE(REPLACE( `file_name` , '.fastq.gz', '' ), '_1', ''), '_2', '');

-- ##taking long time
-- delete FROM `submission` WHERE `accession` IN (
--     Select distinct `Accession` FROM `SRA_Accessions` WHERE  type='SUBMISSION' and `Status` != 'live'
-- );
