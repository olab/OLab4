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
  QuestionResponse,
} from './types';

export const ACTION_SCOPED_OBJECT_DETAILS_SUCCEEDED = (
  scopedObjectId: number,
  scopedObjectDetails: QuestionResponse,
) => {
  const { scopedObjects } = store.getState();
  const scopedObjectsList = scopedObjects.questions;
  const scopedObjectIndex = scopedObjectsList.findIndex(({ id }) => id === scopedObjectId);
  const clonedScopedObject = {
    ...scopedObjectsList[scopedObjectIndex],
    ...scopedObjectDetails,
    isDetailsFetching: false,
  };

  return {
    type: SCOPED_OBJECT_DETAILS_FULFILLED,
    scopedObjectIndex,
    scopedObject: clonedScopedObject,
  };
};

export const ACTION_SCOPED_OBJECT_DETAILS_REQUESTED = (
  questionId: number,
  questionResponseId: number,
) => {
  const { scopedObjects } = store.getState();
  const scopedObjectsList = scopedObjects.questions;
  const scopedObjectIndex = scopedObjectsList.findIndex(({ id }) => id === questionId);
  const clonedScopedObject = {
    ...scopedObjectsList[scopedObjectIndex],
    isDetailsFetching: true,
  };

  return {
    type: SCOPED_OBJECT_DETAILS_REQUESTED,
    questionId,
    questionResponseId,
    scopedObject: clonedScopedObject,
  };
};

export const ACTION_SCOPED_OBJECT_DETAILS_FAILED = (
  scopedObjectId: number,
) => {
  const { scopedObjects } = store.getState();
  const scopedObjectsList = scopedObjects.questions;
  const scopedObjectIndex = scopedObjectsList.findIndex(({ id }) => id === scopedObjectId);
  const clonedScopedObject = {
    ...scopedObjectsList[scopedObjectIndex],
    isDetailsFetching: false,
  };

  return {
    type: SCOPED_OBJECT_DETAILS_FULFILLED,
    scopedObjectIndex,
    scopedObject: clonedScopedObject,
  };
};

export const ACTION_SCOPED_OBJECT_CREATE_SUCCEEDED = (
  scopedObjectId: number,
  scopedObjectData: QuestionResponse,
) => ({
  type: SCOPED_OBJECT_CREATE_SUCCEEDED,
  scopedObjectId,
  scopedObjectData,
});

export const ACTION_SCOPED_OBJECT_CREATE_FAILED = () => ({
  type: SCOPED_OBJECT_CREATE_FAILED,
});

export const ACTION_SCOPED_OBJECT_CREATE_REQUESTED = (
  scopedObjectData: QuestionResponse,
) => ({
  type: SCOPED_OBJECT_CREATE_REQUESTED,
  scopedObjectData,
});

export const ACTION_SCOPED_OBJECT_DELETE_FAILED = () => ({
  type: SCOPED_OBJECT_DELETE_FAILED,
});

export const ACTION_SCOPED_OBJECT_DELETE_SUCCEEDED = (
  scopedObjectId: number,
) => {
  const { scopedObjects } = store.getState();
  const scopedObjectsList = scopedObjects.questions;
  const scopedObjectIndex = scopedObjectsList.findIndex(({ id }) => id === scopedObjectId);

  return {
    type: SCOPED_OBJECT_DELETE_SUCCEEDED,
    scopedObjectIndex,
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
