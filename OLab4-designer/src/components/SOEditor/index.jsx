// @flow
import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import { Route, Switch } from 'react-router-dom';

import SOList from './SOList';

import { SCOPED_OBJECTS_MAPPING } from './config';

import * as scopeLevelsActions from '../../redux/scopeLevels/action';
import * as scopedObjectsActions from '../../redux/scopedObjects/action';

import type { ISOEditorProps } from './types';

class SOEditor extends PureComponent<ISOEditorProps> {
  componentWillUnmount() {
    const {
      ACTION_SCOPE_LEVELS_CLEAR,
      ACTION_SCOPED_OBJECTS_CLEAR,
    } = this.props;

    ACTION_SCOPE_LEVELS_CLEAR();
    ACTION_SCOPED_OBJECTS_CLEAR();
  }

  render() {
    return (
      <Switch>
        <Route
          exact
          path="/scopedObject/:scopedObjectType"
          component={SOList}
        />
        {Object.keys(SCOPED_OBJECTS_MAPPING).map(scopedObjectType => (
          <Route
            key={scopedObjectType}
            exact
            path={[
              `/scopedObject/${scopedObjectType}/add`,
              `/scopedObject/${scopedObjectType}/:scopedObjectId`,
            ]}
            component={SCOPED_OBJECTS_MAPPING[scopedObjectType]}
          />
        ))}
      </Switch>
    );
  }
}

const mapDispatchToProps = dispatch => ({
  ACTION_SCOPE_LEVELS_CLEAR: () => {
    dispatch(scopeLevelsActions.ACTION_SCOPE_LEVELS_CLEAR());
  },
  ACTION_SCOPED_OBJECTS_CLEAR: () => {
    dispatch(scopedObjectsActions.ACTION_SCOPED_OBJECTS_CLEAR());
  },
});

export default connect(
  null,
  mapDispatchToProps,
)(SOEditor);
