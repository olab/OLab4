import createInstance from '../createCustomInstance';
import {
  questionResponseToServer,
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
  .then((
    {
      data: { data: { id: scopedObjectId } },
    },
  ) => scopedObjectId)
  .catch((error) => {
    throw error;
  });

export const editResponse = (scopedObjectData) => {
  const data = questionResponseToServer(scopedObjectData);
  API
    .put(`/olab/questionresponses/${scopedObjectData.id}`, {
      data,
    })
    .catch((error) => {
      throw error;
    });
};
