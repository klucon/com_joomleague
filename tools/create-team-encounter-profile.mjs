import fs from 'node:fs';
const directory = new URL('../administrator/components/com_joomleague/resources/sport-profiles/', import.meta.url);
const profile = JSON.parse(fs.readFileSync(new URL('tennis.json', directory)));
profile.code = 'team_encounter';
profile.version = '1.0.0';
profile.name_key = 'COM_JOOMLEAGUE_PROFILE_TEAM_ENCOUNTER';
profile.description_key = 'COM_JOOMLEAGUE_PROFILE_TEAM_ENCOUNTER_DESC';
profile.sport_type_name_key = 'COM_JOOMLEAGUE_PROFILE_TEAM_ENCOUNTER';
profile.scoring_model = 'team_encounter';
profile.entry_model.allowed_kinds = ['team'];
profile.entry_model.default_kind = 'team';
profile.project_rule_schema = {schema_version:'1.0.0', fields:{}, constraints:[]};
profile.match_structure = {type:'team_encounter', default_start_time:'10:00'};
profile.match.structure = {...profile.match_structure};
profile.match.encounter = {aggregation:'duel_wins', formats:{singles:1,doubles:2}, member_person_types:['player']};
profile.match.score = {type:'nested_score', unit:'duel_wins', value_type:'integer',editor_control:'number',higher_is_better:true,
  segment_types:[
    {code:'duel',parent_code:null,ordinal:10,unit:'score',value_type:'integer',editor_control:'number',repeatable:true,maximum_count:128,name_key:'COM_JOOMLEAGUE_ENCOUNTER_DUEL'},
    {code:'set',parent_code:'duel',ordinal:10,unit:'score',value_type:'integer',editor_control:'number',repeatable:true,maximum_count:128,name_key:'COM_JOOMLEAGUE_ENCOUNTER_SET'}
  ], aggregation:{mode:'none',from:[],final_only:false}};
profile.match.outcome_codes = ['completed'];
profile.statistics = [];
profile.event_types = [];
profile.migration = {joomleague3_sport_keys:[]};
profile.standings.columns = ['rank','team','played','wins','draws','losses','score_for','score_against'];
profile.standings.sort_order = ['wins','score_difference','score_for','name'];
profile.standings.calculation.metrics = profile.standings.calculation.metrics.filter(m=>['played','wins','draws','losses','score_for','score_against','score_difference'].includes(m.code));
profile.standings.calculation.ordering = [
  {metric:'wins',direction:'desc',nulls:'last'},
  {metric:'score_difference',direction:'desc',nulls:'last'},
  {metric:'score_for',direction:'desc',nulls:'last'}
];
fs.writeFileSync(new URL('team-encounter.json',directory),JSON.stringify(profile,null,4)+'\n');
