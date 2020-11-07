import createInstance from '../createCustomInstance';
import {
  questionResponseToServer,
  questionResponseFromServer,
} from '../../helpers/applyAPIMapping';

const API = createInstance();

export const createResponse = (questionId, scopedObjectData) => API
  .post(`/olab/questions/${questionId}/questionresponses`, {
    data: {
      ...questionResponseToServer(scopedObjectData),
    },
  })
  .then(({ data: { data: { id: scopedObjectId } } }) => scopedObjectId)
  .catch((error) => {
    throw error;
  });

export const deleteResponse = (questionId, questionResponseId) => API
  .delete(`/olab/questions/${questionId}/questionresponses/${questionResponseId}`)
  .catch((error) => {
    throw error;
  });

export const editResponse = (scopedObjectData) => API
  .put(`/olab/questions/${scopedObjectData.questionId}/questionresponses/${scopedObjectData.id}`, {
    data: questionResponseToServer(scopedObjectData),
  })
  .catch((error) => {
    throw error;
  });

export const getResponseDetails = (questionId, questionResponseId) => API
  .get(`/olab/questionresponses/${questionResponseId}`)
  .then(({ data: { data: questionResponse } }) => questionResponseFromServer(questionResponse))
  .catch((error) => {
    throw error;
  });

export const getResponses = questionId => API
  .get(`/olab/questions/${questionId}/questionresponses`)
  .then(({ data: { data: { questionResponses } } }) => ({
    questionResponses: questionResponses.map(
      questionResponse => questionResponseFromServer(questionResponse),
    ),
  }))
  .catch((error) => {
    throw error;
  });
