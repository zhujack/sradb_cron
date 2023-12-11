<?php

//XML files to be parsed
/*$xml_files = array(
'submission' => glob($sra_dataPath . '*.submission.xml'),
'study' => glob($sra_dataPath . '*.study.xml'),
'sample' => glob($sra_dataPath . '*.sample.xml'),
'experiment' => glob($sra_dataPath . '*.experiment.xml'),
'run' => glob($sra_dataPath . '*.run.xml')
);*/

//$xml_files = array(
///'submission' => glob($sra_dataPath . '*.submission.xml'),
//'study' => glob($sra_dataPath . '*.study.xml')
 //'sample' => glob($sra_dataPath . '*.sample.xml'),
 //'experiment' => glob($sra_dataPath . '*.experiment.xml'),
 //'run' => glob($sra_dataPath . 'SRA025*.run.xml')
//);
//print_r($xml_files);
//xpath nodes that values need to be concatenated: tag: value | tag2: value2 ... 
$concat_nodes = array(
'sra_link',
'url_link', 
'xref_link',
'entrez_link',
'ddbj_link',
'ena_link',
'read_spec',
'platform_parameters',
'study_attribute',
'sample_attribute',
'experiment_attribute',
'run_attribute',
'submission_attribute',
'related_studies'
);

//xpath for SRA data tyeps
$xpath_sra = array(
'submission' => array(
'alias' => '@alias', 
'accession' => '@accession', 
'submission_comment' => '@submission_comment', //blank
'files' => 'FILES/FILE/@filename',
'center_name' => '@center_name', 
'broker_name' => '@broker_name', //added 
'lab_name' => '@lab_name', 
'submission_date' => '@submission_date',
'sra_link' => 'SUBMISSION_LINKS/SUBMISSION_LINK/SRA_LINK', //new  //blank
'url_link' => 'SUBMISSION_LINKS/SUBMISSION_LINK/URL_LINK', // - //blank
'xref_link' => 'SUBMISSION_LINKS/SUBMISSION_LINK/XREF_LINK', ////blank 
'entrez_link' => 'SUBMISSION_LINKS/SUBMISSION_LINK/ENTREZ_LINK', ////blank
'ddbj_link' => 'SUBMISSION_LINKS/SUBMISSION_LINK/DDBJ_LINK', ////blank
'ena_link' => 'SUBMISSION_LINKS/SUBMISSION_LINK/ENA_LINK', ////blank
'submission_attribute' => 'SUBMISSION_ATTRIBUTES/SUBMISSION_ATTRIBUTE'),//allmost blank

'study' => array(
'alias' => '@alias', 
'accession' => '@accession',
'study_title' => 'DESCRIPTOR/STUDY_TITLE', 
'study_type' => 'DESCRIPTOR/STUDY_TYPE/@existing_study_type', 
'study_abstract' => 'DESCRIPTOR/STUDY_ABSTRACT', 
'center_name' => '@center_name', //added 
'broker_name' => '@broker_name', //added 
//deprecated: 'center_name' => 'DESCRIPTOR/CENTER_NAME', 
'center_project_name' => 'DESCRIPTOR/CENTER_PROJECT_NAME', 
'study_description' => 'DESCRIPTOR/STUDY_DESCRIPTION',
'related_studies' => 'DESCRIPTOR/RELATED_STUDIES/RELATED_STUDY/RELATED_LINK', //new 
'primary_study' => 'DESCRIPTOR/RELATED_STUDIES/RELATED_STUDY/IS_PRIMARY',//new
'sra_link' => 'STUDY_LINKS/STUDY_LINK/SRA_LINK', //new 
'url_link' => 'STUDY_LINKS/STUDY_LINK/URL_LINK', // - not working
'xref_link' => 'STUDY_LINKS/STUDY_LINK/XREF_LINK', //new 
'entrez_link' => 'STUDY_LINKS/STUDY_LINK/ENTREZ_LINK', //not working
'ddbj_link' => 'STUDY_LINKS/STUDY_LINK/DDBJ_LINK', //new
'ena_link' => 'STUDY_LINKS/STUDY_LINK/ENA_LINK', //new
'study_attribute' => 'STUDY_ATTRIBUTES/STUDY_ATTRIBUTE'),

'sample' => array(
'alias' => '@alias', 
'accession' => '@accession', 
'center_name' => '@center_name', //new
'broker_name' => '@broker_name', //added 
'taxon_id' => 'SAMPLE_NAME/TAXON_ID', 
'scientific_name' => 'SAMPLE_NAME/SCIENTIFIC_NAME', //new
'common_name' => 'SAMPLE_NAME/COMMON_NAME', 
'anonymized_name' => 'SAMPLE_NAME/ANONYMIZED_NAME', 
'individual_name' => 'SAMPLE_NAME/INDIVIDUAL_NAME', //new
'description' => 'DESCRIPTION', 
'sra_link' => 'SAMPLE_LINKS/SAMPLE_LINK/SRA_LINK', //new 
'url_link' => 'SAMPLE_LINKS/SAMPLE_LINK/URL_LINK', // - not working
'xref_link' => 'SAMPLE_LINKS/SAMPLE_LINK/XREF_LINK', //new 
'entrez_link' => 'SAMPLE_LINKS/SAMPLE_LINK/ENTREZ_LINK', //not working
'ddbj_link' => 'SAMPLE_LINKS/SAMPLE_LINK/DDBJ_LINK', //new
'ena_link' => 'SAMPLE_LINKS/SAMPLE_LINK/ENA_LINK', //new
'sample_attribute' => 'SAMPLE_ATTRIBUTES/SAMPLE_ATTRIBUTE'
),

'experiment' => array(
'alias' => '@alias', 
'accession' => '@accession', 
'center_name' => '@center_name', 
'broker_name' => '@broker_name', //added 
'title' => 'TITLE', 
'study_name' => 'STUDY_REF/@refname', 
'study_accession' => 'STUDY_REF/@accession', 
'design_description' => 'DESIGN/DESIGN_DESCRIPTION', 
'sample_name' => 'DESIGN/SAMPLE_DESCRIPTOR/@refname', 
'sample_accession' => 'DESIGN/SAMPLE_DESCRIPTOR/@accession', 
'sample_member' => 'DESIGN/SAMPLE_DESCRIPTOR/POOL/MEMBER/@accession', //new
'library_name' => 'DESIGN/LIBRARY_DESCRIPTOR/LIBRARY_NAME', 
'library_strategy' => 'DESIGN/LIBRARY_DESCRIPTOR/LIBRARY_STRATEGY', 
'library_source' => 'DESIGN/LIBRARY_DESCRIPTOR/LIBRARY_SOURCE', 
'library_selection' => 'DESIGN/LIBRARY_DESCRIPTOR/LIBRARY_SELECTION', 
'library_layout' => 'DESIGN/LIBRARY_DESCRIPTOR/LIBRARY_LAYOUT', 
'targeted_loci' => 'DESIGN/LIBRARY_DESCRIPTOR/TARGETED_LOCI/LOCUS/@locus_name',//New
'pooling_strategy' => 'DESIGN/LIBRARY_DESCRIPTOR/POOLING_STRATEGY',
'library_construction_protocol' => 'DESIGN/LIBRARY_DESCRIPTOR/LIBRARY_CONSTRUCTION_PROTOCOL', 
//deprecated: 'spot_decode_method' => 'DESIGN/SPOT_DESCRIPTOR/SPOT_DECODE_METHOD', 
//deprecated: 'number_of_reads_per_spot' => 'DESIGN/SPOT_DESCRIPTOR/SPOT_DECODE_SPEC/NUMBER_OF_READS_PER_SPOT', 
'spot_length' => 'DESIGN/SPOT_DESCRIPTOR/SPOT_DECODE_SPEC/SPOT_LENGTH', //New
'adapter_spec' => 'DESIGN/SPOT_DESCRIPTOR/SPOT_DECODE_SPEC/ADAPTER_SPEC', 
'read_spec' => 'DESIGN/SPOT_DESCRIPTOR/SPOT_DECODE_SPEC/READ_SPEC', //changed 
'platform' => 'PLATFORM', 
'instrument_model' => 'PLATFORM/*/INSTRUMENT_MODEL', 
'platform_parameters' => 'PLATFORM/*', 
'sequence_space' => 'PROCESSING/BASE_CALLS/SEQUENCE_SPACE', 
'base_caller' => 'PROCESSING/BASE_CALLS/BASE_CALLER', 
'quality_scorer' => 'PROCESSING/QUALITY_SCORES/QUALITY_SCORER', 
'number_of_levels' => 'PROCESSING/QUALITY_SCORES/NUMBER_OF_LEVELS', 
'multiplier' => 'PROCESSING/QUALITY_SCORES/MULTIPLIER', 
'qtype' => 'PROCESSING/QUALITY_SCORES/@qtype', 
'sra_link' => 'EXPERIMENT_LINKS/EXPERIMENT_LINK/SRA_LINK', //new 
'url_link' => 'EXPERIMENT_LINKS/EXPERIMENT_LINK/URL_LINK', // - not working
'xref_link' => 'EXPERIMENT_LINKS/EXPERIMENT_LINK/XREF_LINK', //new 
'entrez_link' => 'EXPERIMENT_LINKS/EXPERIMENT_LINK/ENTREZ_LINK', //not working
'ddbj_link' => 'EXPERIMENT_LINKS/EXPERIMENT_LINK/DDBJ_LINK', //new
'ena_link' => 'EXPERIMENT_LINKS/EXPERIMENT_LINK/ENA_LINK', //new
'experiment_attribute' => 'EXPERIMENT_ATTRIBUTES/EXPERIMENT_ATTRIBUTE'),

'run' => array(
'alias' => '@alias', 
'accession' => '@accession', 
//deprecated: 'instrument_model' => '@instrument_model', 
'instrument_name' => '@instrument_name', 
'run_date' => '@run_date', 
////deprecated: 'run_file' => '@run_file', 
'run_center' => '@run_center', 
'broker_name' => '@broker_name', //added 
////deprecated: 'total_data_blocks' => '@total_data_blocks', 
'experiment_accession' => 'EXPERIMENT_REF/@accession', 
'experiment_name' => 'EXPERIMENT_REF/@refname', 
'sra_link' => 'RUN_LINKS/run_LINK/SRA_LINK', //new 
'url_link' => 'RUN_LINKS/RUN_LINK/URL_LINK', // - not working
'xref_link' => 'RUN_LINKS/RUN_LINK/XREF_LINK', //new 
'entrez_link' => 'RUN_LINKS/RUN_LINK/ENTREZ_LINK', //not working
'ddbj_link' => 'RUN_LINKS/RUN_LINK/DDBJ_LINK', //new
'ena_link' => 'RUN_LINKS/RUN_LINK/ENA_LINK', //new
'run_attribute' => 'RUN_ATTRIBUTES/RUN_ATTRIBUTE'),

'data_block' => array(
'name' => '@name', 
'sector' => '@sector', 
'region' => '@region',
//'total_spots' => '@total_spots', 
//'total_reads' => '@total_reads', 
//'number_channels' => '@number_channels', 
//'format_code' => '@format_code',
'files' => 'FILES/FILE'
)
);

?>

<?php /*?>http://trace.ncbi.nlm.nih.gov/Traces/sra/sra.cgi?save=efetch&db=sra&retmode
=xml&rettype=runinfo&term=SRA157949

Run	SRR1239423
ReleaseDate	
LoadDate	
spots	0
bases	0
spots_with_mates	0
avgLength	0
size_MB	0
AssemblyName	
download_path	@dbgap@:reads/SRP028407/SRS615604/SRX518175/SRR1239423/SRR1239423.sra
Experiment	SRX518175
LibraryName	ALZDSP.A-MIR-MR000682-1_1AMP
LibraryStrategy	WXS
LibrarySelection	Hybrid Selection
LibrarySource	GENOMIC
LibraryLayout	PAIRED
InsertSize	214
InsertDev	21.4
Platform	ILLUMINA
Model	Illumina HiSeq 2000
SRAStudy	SRP028407
BioProject	phs000572
Study_Pubmed_id	0
ProjectID	205518
Sample	SRS615604
BioSample	SAMN02483354
TaxID	9606
ScientificName	Homo sapiens
SampleName	A-MIR-MR000682-BL-BOS-4530116
g1k_pop_code	
source	
g1k_analysis_group	
Subject_ID	
Sex	male
Disease	
Tumor	no
Affection_Status	
Analyte_Type	
Histological_Type	
Body_Site	
CenterName	BCM
Submission	SRA157949
dbgap_study_accession	phs000572
Consent	none
RunHash	
ReadHash
<?php */?>