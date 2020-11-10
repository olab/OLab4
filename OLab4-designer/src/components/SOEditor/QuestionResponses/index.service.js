// @flow
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { withStyles } from '@material-ui/core/styles';

import type { IScopedObjectProps } from './types';
import type { ScopedObjectBase as ScopedObjectBaseType } from '../../../redux/questionResponses/types';

import { toLowerCaseAndPlural } from '../utils';
import * as scopedObjectsActions from '../../../redux/questionResponses/action';

import styles from './styles';

export const withQuestionResponseRedux = (
  Component: ReactElement<IScopedObjectProps>,
  scopedObjectType: string,
) => {
  const mapStateToProps = ({ scopedObjects }) => ({
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
    ACTION_SCOPED_OBJECT_CREATE_REQUESTED: (scopedObjectData: ScopedObjectBaseType) => {
      dispatch(scopedObjectsActions.ACTION_SCOPED_OBJECT_CREATE_REQUESTED(
        toLowerCaseAndPlural(scopedObjectType),
        scopedObjectData,
      ));
    },
    ACTION_SCOPED_OBJECT_UPDATE_REQUESTED: (
      scopedObjectId: number,
      scopedObjectData: ScopedObjectBaseType,
    ) => {
      dispatch(scopedObjectsActions.ACTION_SCOPED_OBJECT_UPDATE_REQUESTED(
        scopedObjectId,
        toLowerCaseAndPlural(scopedObjectType),
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
