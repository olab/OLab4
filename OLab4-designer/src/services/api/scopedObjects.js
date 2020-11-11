import createInstance from '../createCustomInstance';
import {
  scopedObjectToServer,
  scopedObjectFromServer,
  scopedObjectByTypeFromServer,
  scopedObjectDetailsFromServer,
} from '../../helpers/applyAPIMapping';

const API = createInstance();

export const getScopedObjects = mapId => API
  .get(`/olab/maps/${mapId}/scopedobjects`)
  .then(({ data: { data: scopedObjects } }) => Object.keys(scopedObjects)
    .reduce((scopedObjectsNew, key) => {
      scopedObjectsNew[key] = scopedObjects[key]
        .map(SO => scopedObjectFromServer(SO));

      return scopedObjectsNew;
    }, {}))
  .catch((error) => {
    throw error;
  });

export const getScopedObjectsByParent = (
  scopedObjectParentType,
  scopedObjectParentId,
  scopedObjectType,
) => API
  .get(`/olab/${scopedObjectParentType}/${scopedObjectParentId}/${scopedObjectType}`)
  .then(({
    data: { data: scopedObjectDetails },
  }) => scopedObjectDetailsFromServer(scopedObjectDetails))
  .catch((error) => {
    throw error;
  });

export const getScopedObjectsByType = scopedObjectType => API
  .get(`/olab/${scopedObjectType}`)
  .then(({ data: { data: scopedObjects } }) => scopedObjects
    .map(SO => scopedObjectByTypeFromServer(SO)))
  .catch((error) => {
    throw error;
  });

export const getScopedObjectDetails = (scopedObjectId, scopedObjectType) => API
  .get(`/olab/${scopedObjectType}/${scopedObjectId}`)
  .then(({
    data: { data: scopedObjectDetails },
  }) => scopedObjectDetailsFromServer(scopedObjectDetails))
  .catch((error) => {
    throw error;
  });

export const createScopedObject = (scopedObjectType, scopedObjectData) => API
  .post(`/olab/${scopedObjectType}`, {
    data: {
      ...scopedObjectToServer(scopedObjectData),
    },
  })
  .then(({ data: { data: { id: scopedObjectId } } }) => scopedObjectId)
  .catch((error) => {
    throw error;
  });

export const editScopedObject = (scopedObjectId, scopedObjectType, editedScopedObjectData) => API
  .put(`/olab/${scopedObjectType}/${scopedObjectId}`, {
    data: {
      ...scopedObjectToServer(editedScopedObjectData),
    },
  })
  .catch((error) => {
    throw error;
  });

export const deleteScopedObject = (scopedObjectId, scopedObjectType) => API
  .delete(`/olab/${scopedObjectType}/${scopedObjectId}`)
  .catch((error) => {
    throw error;
  });

export const getQuestionResponseDetails = (questionId, questionResponseId) => API
  .get(`/olab/questions/${questionId}/questionResponses/${questionResponseId}`)
  .then(({
    data: { data: scopedObjectDetails },
  }) => scopedObjectDetailsFromServer(scopedObjectDetails))
  .catch((error) => {
    throw error;
  });

export const createQuestionResponse = (questionId, questionResponseId, scopedObjectData) => API
  .post(`/olab/questions/${questionId}/questionResponses/${questionResponseId}`, {
    data: {
      ...scopedObjectToServer(scopedObjectData),
    },
  })
  .then(({ data: { data: { id: scopedObjectId } } }) => scopedObjectId)
  .catch((error) => {
    throw error;
  });

export const editQuestionResponse = (questionId, questionResponseId, editedScopedObjectData) => API
  .put(`/olab/questions/${questionId}/questionResponses/${questionResponseId}`, {
    data: {
      ...scopedObjectToServer(editedScopedObjectData),
    },
  })
  .catch((error) => {
    throw error;
  });

export const deleteQuestionResponse = (questionId, questionResponseId) => API
  .delete(`/olab/questions/${questionId}/questionResponses/${questionResponseId}`)
  .catch((error) => {
    throw error;
  });
