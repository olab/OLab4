// @flow
import store from '../../store/store';

import {
  SCOPED_OBJECT_CREATE_FAILED,
  SCOPED_OBJECT_CREATE_REQUESTED,
  SCOPED_OBJECT_CREATE_SUCCEEDED,
  SCOPED_OBJECT_DELETE_FAILED,
  SCOPED_OBJECT_DELETE_SUCCEEDED,
  SCOPED_OBJECT_DETAILS_FULFILLED,
  SCOPED_OBJECT_DETAILS_REQUESTED,
  SCOPED_OBJECT_UPDATE_FULFILLED,
  SCOPED_OBJECT_UPDATE_REQUESTED,
  type QuestionResponse as ScopedObjectType,
} from './types';

export const ACTION_SCOPED_OBJECT_DETAILS_SUCCEEDED = (
  scopedObjectId: number,
  scopedObjectType: string,
  scopedObjectDetails: ScopedObjectType,
) => {
  const storecopy = store.getState();
  const { scopedObjects } = storecopy;
  const scopedObjectsList = scopedObjects[scopedObjectType];
  const scopedObjectIndex = scopedObjectsList.findIndex(({ id }) => id === scopedObjectId);
  const clonedScopedObject = {
    ...scopedObjectsList[scopedObjectIndex],
    ...scopedObjectDetails,
  };

  const response = {
    type: SCOPED_OBJECT_DETAILS_FULFILLED,
    scopedObjectType,
    scopedObjectIndex,
    isFetching: true,
    scopedObject: clonedScopedObject,
  };

  return response;
};

export const ACTION_SCOPED_OBJECT_DETAILS_REQUESTED = (
  questionId: number,
  scopedObjectId: number,
  scopedObjectType: string,
) => {
  const state = store.getState();
  const { questionResponses: { questionresponses } } = state;
  const scopedObjectsList = questionresponses;
  const scopedObjectIndex = scopedObjectsList.findIndex(({ id }) => id === scopedObjectId);
  const clonedScopedObject = {
    ...scopedObjectsList[scopedObjectIndex],
    // isDetailsFetching: true,
  };

  const response = {
    type: SCOPED_OBJECT_DETAILS_REQUESTED,
    questionId,
    scopedObjectId,
    scopedObjectType,
    scopedObjectIndex,
    isFetching: true,
    scopedObject: clonedScopedObject,
  };

  return response;
};

export const ACTION_SCOPED_OBJECT_DETAILS_FAILED = (
  scopedObjectId: number,
  scopedObjectType: string,
) => {
  const { scopedObjects } = store.getState();
  const scopedObjectsList = scopedObjects[scopedObjectType];
  const scopedObjectIndex = scopedObjectsList.findIndex(({ id }) => id === scopedObjectId);
  const clonedScopedObject = {
    ...scopedObjectsList[scopedObjectIndex],
    isDetailsFetching: false,
  };

  return {
    type: SCOPED_OBJECT_DETAILS_FULFILLED,
    scopedObjectType,
    scopedObjectIndex,
    scopedObject: clonedScopedObject,
  };
};

export const ACTION_SCOPED_OBJECT_CREATE_SUCCEEDED = (
  scopedObjectId: number,
  scopedObjectType: string,
  scopedObjectData: ScopedObjectType,
) => ({
  type: SCOPED_OBJECT_CREATE_SUCCEEDED,
  scopedObjectId,
  scopedObjectType,
  scopedObjectData,
});

export const ACTION_SCOPED_OBJECT_CREATE_FAILED = () => ({
  type: SCOPED_OBJECT_CREATE_FAILED,
});

export const ACTION_SCOPED_OBJECT_CREATE_REQUESTED = (
  scopedObjectType: string,
  scopedObjectData: ScopedObjectType,
) => ({
  type: SCOPED_OBJECT_CREATE_REQUESTED,
  scopedObjectType,
  scopedObjectData,
});

export const ACTION_SCOPED_OBJECT_DELETE_FAILED = () => ({
  type: SCOPED_OBJECT_DELETE_FAILED,
});

export const ACTION_SCOPED_OBJECT_DELETE_SUCCEEDED = (
  scopedObjectId: number,
  scopedObjectType: string,
) => {
  const { scopedObjects } = store.getState();
  const scopedObjectsList = scopedObjects[scopedObjectType];
  const scopedObjectIndex = scopedObjectsList.findIndex(({ id }) => id === scopedObjectId);

  return {
    type: SCOPED_OBJECT_DELETE_SUCCEEDED,
    scopedObjectIndex,
    scopedObjectType,
  };
};

export const ACTION_SCOPED_OBJECT_UPDATE_REQUESTED = (
  scopedObjectId: number,
  scopedObjectData: QuestionResponse,
) => ({
  type: SCOPED_OBJECT_UPDATE_REQUESTED,
  scopedObjectId,
  scopedObjectData,
});

export const ACTION_SCOPED_OBJECT_UPDATE_FULFILLED = () => ({
  type: SCOPED_OBJECT_UPDATE_FULFILLED,
});
