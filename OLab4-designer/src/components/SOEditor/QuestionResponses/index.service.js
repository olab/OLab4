// @flow
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { withStyles } from '@material-ui/core/styles';

import { toLowerCaseAndPlural } from '../utils';
import * as questionResponseActions from '../../../redux/questionResponses/action';
import * as scopedObjectsActions from '../../../redux/scopedObjects/action';
import styles from './styles';
import type { IQuestionResponseProps } from './types';
import type { ScopedObjectBase as ScopedObjectBaseType } from '../../../redux/scopedObjects/types';

export const withQuestionResponseRedux = (
  Component: ReactElement<IQuestionResponseProps>,
  scopedObjectType: string,
) => {
  const mapStateToProps = (
    { scopedObjects },
  ) => ({
    scopedObjects: scopedObjects[toLowerCaseAndPlural(scopedObjectType)],
    isScopedObjectCreating: scopedObjects.isCreating,
    isScopedObjectUpdating: scopedObjects.isUpdating,
  });

  const mapDispatchToProps = dispatch => ({
    ACTION_SCOPED_OBJECT_DETAILS_REQUESTED: (scopedObjectId: number) => {
      dispatch(scopedObjectsActions.ACTION_SCOPED_OBJECT_DETAILS_REQUESTED(
        scopedObjectId,
        toLowerCaseAndPlural(scopedObjectType),
      ));
    },
    ACTION_SCOPED_OBJECT_DELETE_REQUESTED: (scopedObjectId: number) => {
      dispatch(questionResponseActions.ACTION_SCOPED_OBJECT_DELETE_REQUESTED(
        scopedObjectId,
      ));
    },
    ACTION_SCOPED_OBJECT_CREATE_REQUESTED: (scopedObjectData: ScopedObjectBaseType) => {
      dispatch(questionResponseActions.ACTION_RESPONSE_CREATE_REQUESTED(
        scopedObjectData,
      ));
    },
    ACTION_SCOPED_OBJECT_UPDATE_REQUESTED: (
      scopedObjectData: ScopedObjectBaseType,
    ) => {
      dispatch(questionResponseActions.ACTION_RESPONSE_UPDATE_REQUESTED(
        scopedObjectData,
      ));
    },
  });

  return connect(
    mapStateToProps,
    mapDispatchToProps,
  )(
    withStyles(styles)(
      withRouter(Component),
    ),
  );
};

export default withQuestionResponseRedux;
