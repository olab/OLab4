// @flow
import type { QuestionResponse } from '../../../redux/questionResponses/types';

export type QuestionResponseEditorProps = {
    classes: {
      [props: string]: any,
    },
    questionId: number,
    questionResponseId: Number,
    questionResponse: QuestionResponse,
    ACTION_UPDATE_QUESTION_RESPONSE: Function,
    ACTION_GET_QUESTION_RESPONSE_REQUESTED: Function,
    ACTION_DELETE_QUESTION_RESPONSE_MIDDLEWARE: Function,    
  };