import createInstance from '../createCustomInstance';
import {
  questionResponseToServer,
  questionResponseFromServer,
} from '../../helpers/applyAPIMapping';

const API = createInstance();

export const deleteResponse = (questionResponseId) => API
  .delete(`/olab/questionresponses/${questionResponseId}`)
  .catch((error) => {
    throw error;
  });

export const createResponse = (scopedObjectData) => API
  .post('/olab/questionresponses', {
    data: {
      ...questionResponseToServer(scopedObjectData),
    },
  })
  .then(({ data: { data: { id: scopedObjectId } } }) => scopedObjectId)
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

export const getResponseDetails = (questionResponseId) => API
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
