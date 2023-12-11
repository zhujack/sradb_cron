#!/bin/bash


## temp
if [[ -f sra_update.log ]];then
    rm -f sra_update.log
fi

touch sra_update.log && chmod 777 sra_update.log



###############################################
## db access
. ~/.db_credential.sh

export dbhost='localhost'
export dbname="sra"

# ## use sra_test database for testing
# export dbname="sra_test"


db_access="-h $dbhost -u $dbuser -p${dbpwd} $dbname"

mysql_dir=/Applications/XAMPP/bin
Yesterday=`date -j -v-1d +%Y%m%d`

###############################################
cd /Users/zhujack/bin/cron_jobs/sra

NCBI_SRA_Metadata_newest="NCBI_SRA_Metadata_${Yesterday}.tar.gz"

if [[ ! -f download/$NCBI_SRA_Metadata_newest ]];then
    echo -e "\n\n\n\n#####################################################################################################" >>  sra_update.log
    echo -e "Updating SRAdb on $Yesterday\n" >>  sra_update.log

    wget -qN ftp://ftp-trace.ncbi.nih.gov/sra/reports/Metadata/${NCBI_SRA_Metadata_newest} -P download/ >> sra_update.log || exit 1
    echo -e "Downloaded ${NCBI_SRA_Metadata_newest} on $Yesterday \n" >> sra_update.log;
    gtar --overwrite -zxf download/${NCBI_SRA_Metadata_newest} -C download/xml/
    echo -e "Uncompressed ${NCBI_SRA_Metadata_newest} to xml folder\n" >> sra_update.log;
    ## mv SRA_Accessions and SRA_Run_Members
else
    echo -e "$NCBI_SRA_Metadata_newest was downloaded\n" >>  sra_update.log
fi



# ## max column width
# awk -F"\t" ' { for (i=1;i<=NF;i++)l[i]=((x=length($i))>l[i]?x:l[i])} END {for(i=1;i<=NF;i++) print "Column"i":",l[i]}' SRA_Run_Members 
# # Column1: 11	Column2: 11	Column3: 11	Column4: 20	Column5: 20	Column6: 20	Column7: 10	Column8: 56	Column9: 17	Column10: 257	Column11: 11	Column12: 11	Column13: 9	Column14: 6	Column15: 11	Column16: 13	Column17: 32	Column18: 14	Column19: 12	Column20: 11

# awk -F"\t" ' { for (i=1;i<=NF;i++)l[i]=((x=length($i))>l[i]?x:l[i])} END {for(i=1;i<=NF;i++) print "Column"i":",l[i]}' SRA_Accessions
# # Column1: 11Column2: 73Column3: 11Column4: 11Column5: 9Column6: 11Column7: 13Column8: 11Column9: 14

###############################################
## maunally extract xml files
# for f in {6..8}
# do
#     wget https://ftp.ncbi.nlm.nih.gov/sra/reports/Metadata/NCBI_SRA_Metadata_2023120${f}.tar.gz -P download/
# done
#
#
# for f in download/NCBI_SRA_Metadata_2023*.tar.gz
# do
#     echo $f
#     gtar --overwrite  -zxf $f -C download/xml/
# done


################################################
## update the database on Thursday night every week
Day=$(date +%u)

## '8' for exit for the moment
if [[ ! $Day -eq 8 ]]; then
	exit 1
fi

echo -e "\n not going to update for $Yesterday\n" >> sra_update.log;


# ################################################
# ## truncate table SRA_Accessions, SRA_Run_Members and fastqFileReport; create or truncate table sra_new
${mysql_dir}/mysql $db_access < scripts/prepare_sra.sql >> sra_update.log  2>&1

cp -f download/xml/SRA_Run_Members download/xml/SRA_Accessions download/

## create a 5000-line test file for SRA_Accessions and SRA_Run_Members - shuf is slow
if [[ "$dbname" == "sra_test" ]];then
    mv download/SRA_Accessions download/SRA_Accessions_full

    cat <(head -1 download/SRA_Accessions_full) <(tail -n+2 download/SRA_Accessions_full | head -5) > download/SRA_Accessions
else
    # controlled_access 7,771,159
    # public 95,061,685

    ## rebuild table SRA_Run_Members
    ${mysql_dir}/mysqlimport --local --ignore-lines=1 $db_access --columns=Run,Member_Name,Experiment,Sample,Study,Spots,Bases,Status,BioSample download/SRA_Run_Members >> sra_update.log  2>&1
fi

## rebuild table SRA_Accessions
${mysql_dir}/mysqlimport --local --ignore-lines=1 $db_access --columns=Accession,Submission,Status,Updated,Published,Received,Type,Center,Visibility,Alias,Experiment,Sample,Study,Loaded,Spots,Bases,Md5sum,BioSample,BioProject,ReplacedBy download/SRA_Accessions >> sra_update.log  2>&1


################################################
# ### Download fastqlist - does not exist anymore
# wget -N --spider ftp://ftp.sra.ebi.ac.uk/meta/list/fastqFileReport.gz 2> fastqFileReport.log
# not_retrieving=`grep 'not retrieving' fastqFileReport.log | wc -l`
# ## only update on new files
# if [[ "${not_retrieving}" -eq 0 ]]; then
#     /usr/local/bin/wget -qN ftp://ftp.sra.ebi.ac.uk/meta/list/fastqFileReport.gz >> sra_update.log  2>&1
#     /usr/local/bin/wget -qN ftp://ftp.sra.ebi.ac.uk/meta/list/livelist.gz >> sra_update.log  2>&1
#     echo "Downloaded fastqFileReport.gz and livelist.gz on $(date +%c)" >> sra_update.log;
#     gunzip -c fastqFileReport.gz > fastq
#
#     ## rebuild table fastqlist
#     ${mysql_dir}/mysqlimport --local --ignore-lines=1 -h localhost -u zhujack -pmicroarray  sra --columns=RUN_ID,RUN_ALIAS,EXPERIMENT_ID,EXPERIMENT_ALIAS,SAMPLE_ID,SAMPLE_ALIAS,STUDY_ID,STUDY_ALIAS,LIBRARY_LAYOUT,INSTRUMENT_PLATFORM,STUDY_TYPE,FASTQ_FILES,TAX_ID,SCIENTIFIC_NAME,COMMON_NAME fastq >> sra_update.log  2>&1
#     echo "Rebuild SRA_Accessions, SRA_Run_Members and fastq tables on $(date +%c)" >> sra_update.log;
# fi
#
# ## for automation
# chmod 777 


#################################################
## parse and update sra metedata; update bam files;
echo -e "\nStarted update sra records @ `date` \n" >> sra_update.log  2>&1
php scripts/sra_parser.php  >> sra_update.log  2>&1
echo -e "\nParsed and updated sra metadata on $(date +%c)" >> sra_update.log;



#####################################################################################################"
echo -e "#####################################" >>  sra_update.log
## insert 'Ghost' records
echo -e "## Inserted adding 'Ghost' records @ `date` \n" >>  sra_update.log
mysql $db_access < scripts/add_sra_records.sql >>  sra_update.log  2>&1

##################################################
## generate sra_new contents from other tables and update the sra_new
echo -e "\n\n############################################" >>  sra_update.log
echo "## Generate sra_new.csv @ `date` \n" >>  sra_update.log
R --no-save < scripts/sra_new.R  >>  sra_update.log  2>&1
chmod 777 sra_new.csv

echo -e "\n\n############################################" >>  sra_update.log
echo "## import sra_new.csv @ `date` \n" >>  sra_update.log
${mysql_dir}/mysqlimport --local --ignore-lines=1 --fields-enclosed-by='"' --fields-terminated-by="," $db_access sra_new.csv 

echo -e "\n\n############################################" >>  sra_update.log
echo "## rename sra tables @ `date` \n" >>  sra_update.log
## DROP TABLE `sra`.`sra_backup`;RENAME TABLE `sra`.`sra` TO `sra`.`sra_backup`; RENAME TABLE `sra`.`sra_new` TO `sra`.`sra`;
${mysql_dir}/mysql $db_access < scripts/sra_mv.sql  >>  sra_update.log  2>&1

## keep a copy of sra_new.csv
mv sra_new.csv sqlite_db

#################################################
## generate SQLite SRAmetadb

if [ -f SRAmetadb.sqlite ]; then
    rm -f SRAmetadb.sqlite
fi
if [ -f sqlite_db/SRAmetadb.sqlite.gz ]; then
    mv -f sqlite_db/SRAmetadb.sqlite.gz sqlite_db/SRAmetadb.sqlite_`date -j  +%Y%m%d`.gz
fi

echo -e "\n\n############################################" >>  sra_update.log
echo "## Generate SRAmetadb on $(date +%c)" >>  sra_update.log;
R --no-save < scripts/sraSQLite.R >>  sra_update.log  2>&1

mv -f SRAmetadb.sqlite.gz sqlite_db/

echo -e "\n\n############################################" >>  sra_update.log
echo "## Fiish generating SRAmetadb.sqlite.gz on $(date +%c)" >>  sra_update.log



# ################################################
# ### Dump MySQL SRAdb

echo -e "\n\n############################################" >>  sra_update.log
echo "## Started generated SRAdb.mysqldump.gz on $(date +%c)" >>  sra_update.log

if [ -f mysql_dump/SRAdb.mysqldump.gz ]; then
    mv -f mysql_dump/SRAdb.mysqldump.gz  mysql_dump/SRAdb.mysqldump_`date -j  +%Y%m%d`.gz;
fi

${mysql_dir}/mysqldump $db_access col_desc data_block experiment run sample sra SRA_Accessions SRA_Run_Members study submission fastq | gzip > SRAdb.mysqldump.gz

mv SRAdb.mysqldump.gz mysql_dump/

echo "Finished generated SRAdb.mysqldump.gz on $(date +%c)" >>  sra_update.log;

