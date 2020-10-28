// @flow
import React from 'react';
import { SCOPE_LEVELS, SCOPED_OBJECTS } from '../../config';
import type { IQuestionResponseProps } from './types';
import ScopedObjectService, { withSORedux } from '../index.service';
import { FieldLabel } from '../styles';
import { EDITORS_FIELDS } from '../config';

class QuestionResponses extends ScopedObjectService {
  constructor(props: IQuestionResponseProps) {
    super(props, SCOPED_OBJECTS.QUESTIONRESPONSES.name);
    this.state = {
      scopeLevel: SCOPE_LEVELS[0],
    };
  }

  render() {
    return (
      <>
        <FieldLabel>
          {EDITORS_FIELDS.LAYOUT_TYPE}
        </FieldLabel>
      </>
    );
  }
}

export default withSORedux(QuestionResponses, SCOPED_OBJECTS.QUESTIONRESPONSES.name);
