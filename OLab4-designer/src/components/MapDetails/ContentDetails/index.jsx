// @flow
import React from 'react';

import Switch from '../../../shared/components/Switch';
import TextEditor from '../../../shared/components/TextEditor';

import { CONTENT_DETAILS_CHECKBOXES } from './config';

import type { ContentDetailsProps as IProps } from './types';

import { ContainerTab, ContentTitle } from '../styles';
import { ContainerCheckBox, CheckBox } from './styles';

const ContentDetails = ({
  details, handleEditorChange, handleCheckBoxChange,
}: IProps): React$Element<any> => (
  <ContainerTab>
    <ContentTitle>Authoring notes</ContentTitle>
    <TextEditor
      editorId="notes"
      width={800}
      height={300}
      text={details.notes}
      handleEditorChange={handleEditorChange}
    />
    <ContainerCheckBox>
      {CONTENT_DETAILS_CHECKBOXES.map(({ label, name }, index) => {
        const key = label + index;

        return (
          <CheckBox key={key}>
            <Switch
              labelPlacement="start"
              name={name}
              label={label}
              checked={details[name]}
              onChange={handleCheckBoxChange}
            />
          </CheckBox>
        );
      })}
    </ContainerCheckBox>
  </ContainerTab>
);

export default ContentDetails;
