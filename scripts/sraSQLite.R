## sraSQLite - R script for creating a SQLite database of SRA metadata from a MYSQL database
## Jack Zhu @022810
## Comments and suggestions are welcome.

################################################################################
## Script description:
## sraSQLite - generates a SQLite db file (.gz file) of SRA metadata from 
## selected SRA tables and fileds in MySQL database of SRA metadata, located in amnesia;
## Main tables: study, sample, experiment, run, data_block
## Converting tables: 


################################################################################

dbuser=Sys.getenv('dbuser')
dbpwd=Sys.getenv('dbpwd')
dbname=Sys.getenv('dbname')
dbhost=dbhost="127.0.0.1"

sqlite_db_name = 'SRAmetadb.sqlite'
wd = "."
index = T

library(RMySQL);
library(RSQLite);

##MySQL and SQLite connections;
mysql_con <- dbConnect(MySQL(), user=dbuser, password=dbpwd, dbname=dbname, host=dbhost);
sqlite_con <- dbConnect(dbDriver("SQLite"), dbname = paste(wd,"/", sqlite_db_name,sep="")); 

##create metaInfo table, with two columns, name and value;
dbGetQuery(sqlite_con, "create table metaInfo (name varchar(50), value varchar(50)) ;");
dbGetQuery(sqlite_con, "insert into metaInfo values ('schema version','1.0') ;");
dbGetQuery(sqlite_con, "insert into metaInfo values ('creation timestamp',DATETIME('NOW')) ;");

# ## try to resolve MySQL timeout problem
# dbGetQuery(mysql_con, 'SET GLOBAL connect_timeout=28800')
# dbGetQuery(mysql_con, 'SET GLOBAL wait_timeout=28800')
# dbGetQuery(mysql_con, 'SET GLOBAL interactive_timeout=28800')


dbGetQuery(sqlite_con, "PRAGMA cache_size = 100000");
## dbGetQuery(sqlite_con, "PRAGMA auto_vacuum =  1");		
## tables = c('submission','study','sample','experiment','run','data_block','sra','col_desc')
## tables = c('submission','study','sample','experiment','run','sra','col_desc','fastq')
tables = c('submission','study','sample','experiment','run','sra','col_desc')


mysql_tables_fields = list()
for(table in tables) mysql_tables_fields[eval(table)] = list(dbListFields(mysql_con, table));

## SQLite tables and  index names and index fileds;
# sqlite_tables_indice <- list(fastq = c(sra_fastq_file_name_idx = "file_name"), submission = c(submission_acc_idx = "submission_accession"),study = c(study_acc_idx = "study_accession"), sample = c(sample_acc_idx = "sample_accession"), experiment = c(experiment_acc_idx = "experiment_accession"), run = c(run_acc_idx = "run_accession"), sra = c(sra_run_acc_idx = "run_accession", sra_experiment_acc_idx = "experiment_accession", sra_sample_acc_idx = "sample_accession", sra_study_acc_idx = "study_accession", sra_submission_acc_idx = "submission_accession" ));

sqlite_tables_indice <- list(submission = c(submission_acc_idx = "submission_accession"),study = c(study_acc_idx = "study_accession"), sample = c(sample_acc_idx = "sample_accession"), experiment = c(experiment_acc_idx = "experiment_accession"), run = c(run_acc_idx = "run_accession"), sra = c(sra_run_acc_idx = "run_accession", sra_experiment_acc_idx = "experiment_accession", sra_sample_acc_idx = "sample_accession", sra_study_acc_idx = "study_accession", sra_submission_acc_idx = "submission_accession" ));
						
print(paste("Total tables: ",length(mysql_tables_fields), sep =""));
dbDisconnect(mysql_con);

##Query MySQL and insert dataframes into SQLite DB;
for( table in names(mysql_tables_fields) ) {
    ##MySQL
    mysql_con <- dbConnect(MySQL(), user=dbuser, password=dbpwd, dbname=dbname, host=dbhost);
    # ## try to resolve MySQL timeout problem
    dbGetQuery(mysql_con, 'SET GLOBAL connect_timeout=28800')
    dbGetQuery(mysql_con, 'SET GLOBAL wait_timeout=28800')
    dbGetQuery(mysql_con, 'SET GLOBAL interactive_timeout=28800')
    
    sql_cmd <- paste("select * from ", table, sep = '');
	rs <- dbGetQuery(mysql_con, sql_cmd);
    dbDisconnect(mysql_con);
    	
    ##change SQLite table filed names of 'sra_accession' to corresponding names
	names(rs)[ names(rs) %in%  c('ID', 'alias', 'accession', 'url_link', 'entrez_link') ] = paste(table, '_', names(rs)[names(rs) %in%  c('ID', 'alias', 'accession', 'url_link', 'entrez_link')], sep='')

	## rename fastq_accession to run_accession
	if( table == 'fastq' ) {
		names(rs) <- sub( 'fastq_accession', 'run_accession', names(rs) )
	}
							
	dbWriteTable(sqlite_con, table, rs, row.names = F, overwrite = T, append = F);
	
	##sql for creating fulltext search table of sra_ft from sra
	if(table=='sra') {
		sra_ft_fileds = paste(names(rs)[-1], collapse=',')
		create_sra_ft = paste("CREATE VIRTUAL TABLE sra_ft USING fts3 (", sra_ft_fileds, ");")
		insert_sra_ft = paste("insert into sra_ft (docid,", sra_ft_fileds ,") select * from sra ;")
		cmd_sra_ft = paste ("sqlite3 SRAmetadb.sqlite '", create_sra_ft, insert_sra_ft, "'",  sep='') 
		system(cmd_sra_ft)
		print("Creating sra_ft ...")
	}
	
	## create indice in SQLite databases;		
	if(index) {		
		for( index_j in names(sqlite_tables_indice[[table]]) ) {
			sql_idx = paste("create index ", index_j, " on ", table, " (", sqlite_tables_indice[[table]][[index_j]] ,")", sep = ""); 
			dbSendQuery(sqlite_con, sql_idx);
		}
	}
	print( paste(table, ": done", sep="") );
}## end for each table;


##Verify SQLite DB
for(k in (1:length(sqlite_tables_indice))) {
	sqlite_tables_k <- names(sqlite_tables_indice)[k];
	sql_cmd_1 <- paste("select count(*) from", sqlite_tables_k, sep=" ");
	rs <- dbGetQuery(sqlite_con, sql_cmd_1);
	print(paste(sqlite_tables_k,"count:", rs, sep = " "));
}
	
dbDisconnect(sqlite_con);

##gz file the SQLite db;
gz_cmd = paste("gzip ", paste(wd,"/",sqlite_db_name, sep = "") , sep = "");
system(gz_cmd);


