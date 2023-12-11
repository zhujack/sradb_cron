## sra_mv - R script for generating sra table contents from othe tables
## Jack Zhu @122911
## Comments and suggestions are welcome.

Sys.setenv('R_MAX_VSIZE'=200000000000)

dbuser=Sys.getenv('dbuser')
dbpwd=Sys.getenv('dbpwd')
dbname=Sys.getenv('dbname')
dbhost=dbhost="127.0.0.1"

wd = "."
##
library(RMySQL);	

tables = c('study','experiment','run','sample','submission')

tables_fields <- list(
'submission'=c('accession','center_name','ID','lab_name','submission_comment','submission_date'),
'run'=c('accession','alias','bamFile','entrez_link','experiment_name','ID','instrument_name','run_attribute','run_center','run_date','url_link','submission_accession'),
'sample'=c('accession','alias','anonymized_name','common_name','description','entrez_link','ID','individual_name','sample_attribute','taxon_id','url_link'),
'study'=c('accession','alias','center_project_name','entrez_link','ID','primary_study','related_studies','study_abstract','study_attribute','study_description','study_title','study_type','url_link','xref_link'),
'experiment'=c('accession','adapter_spec','alias','bamFile','base_caller','design_description','entrez_link','experiment_attribute','fastqFTP','ID','instrument_model','library_construction_protocol','library_layout','library_name','library_selection','library_source','library_strategy','multiplier','number_of_levels','platform','platform_parameters','qtype','quality_scorer','read_spec','sample_name','sequence_space','study_name','title','url_link')
)

tables_fields_new <- list(
'submission'=c('submission_accession' ,'submission_center' ,'submission_ID' ,'submission_lab' ,'submission_comment' ,'submission_date'),
'run'=c('run_accession','run_alias','SRR_bamFile','run_entrez_link','experiment_name','run_ID','instrument_name','run_attribute','run_center','run_date','run_url_link','submission_accession'),
'sample'=c('sample_accession','sample_alias','anonymized_name','common_name','description','sample_entrez_link','sample_ID','individual_name','sample_attribute','taxon_id','sample_url_link'),
'study'=c('study_accession','study_alias','center_project_name','study_entrez_link','study_ID','primary_study','related_studies','study_abstract','study_attribute','study_description','study_title','study_type','study_url_link','pubmed'),
'experiment'=c('experiment_accession','adapter_spec','experiment_alias','SRX_bamFile','base_caller','design_description','experiment_entrez_link','experiment_attribute','SRX_fastqFTP','experiment_ID','instrument_model','library_construction_protocol','library_layout','library_name','library_selection','library_source','library_strategy','multiplier','number_of_levels','platform','platform_parameters','qtype','quality_scorer','read_spec','sample_name','sequence_space','study_name','experiment_title','experiment_url_link')
)

##MySQL and SQLite connections;
mysql_con <- dbConnect(MySQL(), user=dbuser, password=dbpwd, dbname=dbname, host=dbhost);

sra_fileds_ordered = dbListFields(mysql_con, 'sra');
sra_fileds_ordered <- sra_fileds_ordered[-c(1, length(sra_fileds_ordered))]

sra <- dbGetQuery(mysql_con, "select DISTINCT Updated as updated_date, Spots as spots, Bases as bases, Study as study_accession, Experiment as experiment_accession, Accession as run_accession,Sample as sample_accession from SRA_Accessions WHERE status='live' and Type='RUN' ");

dbDisconnect(mysql_con);


for (table in tables) {
    mysql_con <- dbConnect(MySQL(), user=dbuser, password=dbpwd, dbname=dbname, host=dbhost);
	sra1 <-  dbGetQuery(mysql_con, paste("select ", paste(tables_fields[[table]], collapse=','), ' from ', table, sep='') ); 
    dbDisconnect(mysql_con);
    
	names(sra1) <- tables_fields_new[[table]]
	sra <- merge(sra, sra1, by=paste(table, '_accession', sep=''), all.x=TRUE)	
}

sra <- sra[, sra_fileds_ordered]
sra <- cbind(ID="", sra)
write.csv(sra, file='sra_new.csv', na='NULL', row.names=FALSE)




