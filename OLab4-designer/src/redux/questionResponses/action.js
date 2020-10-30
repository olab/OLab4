// @flow
import store from '../../store/store';

import {
  CREATE_QUESTION_RESPONSE_FAILED,
  CREATE_QUESTION_RESPONSE_SUCCEEDED,
  CREATE_QUESTION_RESPONSE,
  DELETE_QUESTION_RESPONSE_FULLFILLED,
  DELETE_QUESTION_RESPONSE_REQUESTED,
  DELETE_QUESTION_RESPONSE_SYNC,
  EXCHANGE_QUESTION_RESPONSE_ID,
  GET_QUESTION_RESPONSE_FAILED,
  GET_QUESTION_RESPONSE_FULLFILLED,
  GET_QUESTION_RESPONSE_REQUESTED,
  SELECT_QUESTION_RESPONSE,
  UPDATE_QUESTION_RESPONSE,
  QuestionResponse,
} from './types';

export const ACTION_GET_QUESTION_RESPONSE_FULLFILLED = (
  initialQuestionResponse: QuestionResponse,
) => {
  const { question: { nodes: questionResponses } } = store.getState();
  const {
    isFocused = false,
    isSelected = false,
  } = questionResponses.find(item => item.id === initialQuestionResponse.id) || {};
  const index = questionResponses.findIndex(({ id }) => id === initialQuestionResponse.id);
  const node = {
    ...initialQuestionResponse,
    isFocused,
    isSelected,
  };

  return {
    type: GET_QUESTION_RESPONSE_FULLFILLED,
    index,
    node,
  };
};

export const ACTION_GET_QUESTION_RESPONSE_REQUESTED = (
  questionId: number,
  questionResponseId: number,
) => ({
  type: GET_QUESTION_RESPONSE_REQUESTED,
  questionId,
  questionResponseId,
});

export const ACTION_DELETE_QUESTION_RESPONSE_FULLFILLED = () => ({
  type: DELETE_QUESTION_RESPONSE_FULLFILLED,
});

export const ACTION_UPDATE_QUESTION_RESPONSE = (
  questionResponseData: QuestionResponse,
  isShowNotification: boolean = false,
  mapIdFromURL: number,
) => {
  const { question: { questionResponses } } = store.getState();
  const questionResponseIndex = questionResponses.findIndex(
    ({ id }) => id === questionResponseData.id,
  );
  const questionResponse = {
    ...questionResponses[questionResponseIndex],
    ...questionResponseData,
  };

  return {
    type: UPDATE_QUESTION_RESPONSE,
    questionResponseIndex,
    questionResponse,
    isShowNotification,
    mapIdFromURL,
  };
};

export const ACTION_EXCHANGE_QUESTION_RESPONSE_ID = (
  oldQuestionResponseId: number | string,
  newQuestionResponseId: number,
) => {
  const { question: { questionResponses } } = store.getState();
  const questionResponseIndex = questionResponses.findIndex(
    ({ id }) => id === oldQuestionResponseId,
  );

  const clonedQuestionResponse = {
    ...questionResponses[questionResponseIndex],
    id: newQuestionResponseId,
  };

  return {
    type: EXCHANGE_QUESTION_RESPONSE_ID,
    questionResponseIndex,
    questionResponse: clonedQuestionResponse,
  };
};
