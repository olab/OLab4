import createInstance from '../createCustomInstance';
import {
  questionResponseToServer,
  questionResponseFromServer,
} from '../../helpers/applyAPIMapping';

const API = createInstance();

export const getQuestionResponse = (questionId, questionResponseId) => API
  .get(`/olab/questions/${questionId}/questionResponses/${questionResponseId}`)
  .then(({ data: { data: questionResponse } }) => questionResponseFromServer(questionResponse))
  .catch((error) => {
    throw error;
  });

export const getQuestionResponses = questionId => API
  .get(`/olab/questions/${questionId}/questionResponses`)
  .then(({ data: { data: { questionResponses } } }) => ({
    questionResponses: questionResponses.map(
      questionResponse => questionResponseFromServer(questionResponse),
    ),
  }))
  .catch((error) => {
    throw error;
  });

export const createQuestionResponse = (questionId, sourceQuestionResponseId) => API
  .post(`/olab/questions/${questionId}/questionResponses`, {
    data: {
      ...(sourceQuestionResponseId && { sourceId: sourceQuestionResponseId }),
    },
  })
  .then(({ data: { data } }) => {
    const { id: newQuestionResponseId } = data;
    return newQuestionResponseId;
  })
  .catch((error) => {
    throw error;
  });

export const updateQuestionResponse = (questionId, updatedQuestionResponse) => API
  .put(`/olab/questions/${questionId}/questionResponses/${updatedQuestionResponse.id}`, {
    data: questionResponseToServer(updatedQuestionResponse),
  })
  .catch((error) => {
    throw error;
  });

export const deleteQuestionResponse = (questionId, questionResponseId) => API
  .delete(`/olab/questions/${questionId}/questionResponses/${questionResponseId}`)
  .catch((error) => {
    throw error;
  });
