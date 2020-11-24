// @flow
import store from '../../store/store';

import {
  RESPONSE_CREATE_FAILED,
  RESPONSE_CREATE_REQUESTED,
  RESPONSE_CREATE_SUCCEEDED,
  RESPONSE_DELETE_FAILED,
  RESPONSE_DELETE_REQUESTED,
  RESPONSE_DELETE_SUCCEEDED,
  RESPONSE_UPDATE_FULFILLED,
  RESPONSE_UPDATE_REQUESTED,
  type QuestionResponse as ScopedObjectType,
} from './types';

export const ACTION_RESPONSE_CREATE_SUCCEEDED = (
  scopedObjectId: number,
  scopedObjectData: ScopedObjectType,
) => ({
  type: RESPONSE_CREATE_SUCCEEDED,
  scopedObjectId,
  scopedObjectData,
});

export const ACTION_RESPONSE_CREATE_FAILED = () => ({
  type: RESPONSE_CREATE_FAILED,
});

export const ACTION_RESPONSE_CREATE_REQUESTED = (
  scopedObjectData: ScopedObjectType,
) => ({
  type: RESPONSE_CREATE_REQUESTED,
  scopedObjectData,
});

export const ACTION_SCOPED_OBJECT_DELETE_REQUESTED = (
  scopedObjectId: number,
  // scopedObjectType: string,
) => ({
  type: RESPONSE_DELETE_REQUESTED,
  scopedObjectId,
  // scopedObjectType,
});

export const ACTION_RESPONSE_DELETE_FAILED = () => ({
  type: RESPONSE_DELETE_FAILED,
});

// export const ACTION_RESPONSE_DELETE_SUCCEEDED = (scopedObjectId) => ({
//   type: RESPONSE_DELETE_SUCCEEDED,
//   scopedObjectId,
// });

export const ACTION_RESPONSE_DELETE_SUCCEEDED = (
  scopedObjectId: number,
) => {
  const scopedObjectType = 'questions';
  const { scopedObjects } = store.getState();
  const scopedObjectsList = scopedObjects[scopedObjectType][0].responses;
  const scopedObjectIndex = scopedObjectsList.findIndex(({ id }) => id === scopedObjectId);

  return {
    type: RESPONSE_DELETE_SUCCEEDED,
    scopedObjectIndex,
    scopedObjectType,
    scopedObject: scopedObjects[scopedObjectType][0],
  };
};

export const ACTION_RESPONSE_UPDATE_REQUESTED = (
  scopedObjectData: Array,
) => ({
  type: RESPONSE_UPDATE_REQUESTED,
  scopedObjectData,
});

export const ACTION_RESPONSE_UPDATE_FULFILLED = () => ({
  type: RESPONSE_UPDATE_FULFILLED,
});
