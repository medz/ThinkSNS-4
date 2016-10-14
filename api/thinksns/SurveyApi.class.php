<?php

//调查问卷接口
class SurveyApi extends Api
{
    //查看所有调查问卷
    public function showAll()
    {
        return D('SurveyAnswer', 'survey')->findall();
    }
    //参与问卷调查
    public function joinSurvey()
    {
        $res = D('SurveyAnswer', 'survey')->saveAnswer($this->mid, $this->data['survey_id'], $this->data['question']);
        if ($res == false) {
            return 0;
        } else {
            return 1;
        }
    }
    //查看问卷调查
    public function scanSurvey()
    {
        $uid = intval($this->data['uid']);
        $survey_id = intval($this->data['id']);
        if (empty($uid) || empty($survey_id)) {
            return 0;
        }
        $data['survey'] = D('Survey', 'survey')->getSurvey($survey_id);
        $data['questions'] = D('Survey', 'survey')->getSurveyQuestions($survey_id);
        $data['answer'] = D('SurveyAnswer', 'survey')->getAnswer($survey_id, $uid);

        return $data;
    }
}
