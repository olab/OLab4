// @flow
export type QuestionResponse = {
  acl: string,
  created_at: string,
  description: string,
  feedback: string,
  from: string,
  id: number,
  is_correct: boolean,
  isDetailsFetching: boolean,
  name: string,
  order: number,
  parent_id: number,
  question_id: number,
  response: string,
  score: number,
  to: string,
  updated_At: string,
};

export type QuestionResponseListItem = {
  id: number,
  ...QuestionResponse,
}

const EXCHANGE_QUESTION_RESPONSE_ID = 'EXCHANGE_QUESTION_RESPONSE_ID';
type ExchangeQuestionResponseId = {
  type: 'EXCHANGE_QUESTION_RESPONSE_ID',
  questionResponseIndex: number,
  questionResponse: QuestionResponse,
};

const CREATE_QUESTION_RESPONSE_FAILED = 'CREATE_QUESTION_RESPONSE_FAILED';
type CreateQuestionResponseFailed = {
  type: 'CREATE_QUESTION_RESPONSE_FAILED',
};

const SELECT_QUESTION_RESPONSE = 'SELECT_QUESTION_RESPONSE';
type SelectQuestionResponse = {
  type: 'SELECT_QUESTION_RESPONSE',
  nodes: Array<QuestionResponse>,
};

const DELETE_QUESTION_RESPONSE_SYNC = 'DELETE_QUESTION_RESPONSE_SYNC';
type DeleteQuestionResponseSync = {
  type: 'DELETE_QUESTION_RESPONSE_SYNC',
  questionResponseIndex: number,
};

const CREATE_QUESTION_RESPONSE = 'CREATE_QUESTION_RESPONSE';
type CreateQuestionResponse = {
  type: 'CREATE_QUESTION_RESPONSE',
  questionResponse: QuestionResponse,
};

const UPDATE_QUESTION_RESPONSE = 'UPDATE_QUESTION_RESPONSE';
type UpdateQuestionResponse = {
  type: 'UPDATE_QUESTION_RESPONSE',
  index: number,
  questionResponse: QuestionResponse,
  isShowNotification: boolean,
};

const DELETE_QUESTION_RESPONSE_REQUESTED = 'DELETE_QUESTION_RESPONSE_REQUESTED';
type DeleteQuestionResponseRequested = {
  type: 'DELETE_QUESTION_RESPONSE_REQUESTED',
  questionId: number,
  questionResponseId: number,
  questionResponseIndex: number,
};

const GET_QUESTION_RESPONSE_REQUESTED = 'GET_QUESTION_RESPONSE_REQUESTED';
type GetQuestionResponseRequested = {
  type: 'GET_QUESTION_RESPONSE_REQUESTED',
  questionId: number,
  questionResponseId: number,
};

const GET_QUESTION_RESPONSE_FULLFILLED = 'GET_QUESTION_RESPONSE_FULLFILLED';
type GetQuestionResponseFullfilled = {
  type: 'GET_QUESTION_RESPONSE_FULLFILLED',
  index: number,
  questionResponse: QuestionResponse,
};

const DELETE_QUESTION_RESPONSE_FULLFILLED = 'DELETE_QUESTION_RESPONSE_FULLFILLED';
type DeleteQuestionResponseFullFilled = {
  type: 'DELETE_QUESTION_RESPONSE_FULLFILLED',
};

export type QuestionResponseActions =
  SelectQuestionResponse | ExchangeQuestionResponseId | CreateQuestionResponseFailed |
  CreateQuestionResponse | UpdateQuestionResponse | DeleteQuestionResponseRequested |
  GetQuestionResponseRequested | GetQuestionResponseFullfilled | DeleteQuestionResponseFullFilled |
  DeleteQuestionResponseSync;

export {
  CREATE_QUESTION_RESPONSE_FAILED,
  CREATE_QUESTION_RESPONSE,
  DELETE_QUESTION_RESPONSE_FULLFILLED,
  DELETE_QUESTION_RESPONSE_REQUESTED,
  DELETE_QUESTION_RESPONSE_SYNC,
  EXCHANGE_QUESTION_RESPONSE_ID,
  GET_QUESTION_RESPONSE_FULLFILLED,
  GET_QUESTION_RESPONSE_REQUESTED,
  SELECT_QUESTION_RESPONSE,
  UPDATE_QUESTION_RESPONSE,
};
