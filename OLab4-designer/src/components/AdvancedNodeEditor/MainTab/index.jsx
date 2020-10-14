// @flow
import React from 'react';

import Switch from '../../../shared/components/Switch';
import TextEditor from '../../../shared/components/TextEditor';
import OutlinedInput from '../../../shared/components/OutlinedInput';

import { ORDINARY_TYPE as ORDINARY_NODE_TYPE } from '../../Constructor/Graph/Node/config';

import type { MainTabProps as IProps } from './types';

import {
  TextContent, OtherContent, NodeContentTitle, Container,
} from './styles';

const MainTab = ({
  text = '', title = '', type = 0, isEnd = false, isVisitOnce = false,
  handleTitleChange, handleEditorChange, handleCheckBoxChange, handleKeyDown,
}: IProps) => {
  const checkBoxes = [
    { label: 'Root Node', value: type, name: 'type' },
    { label: 'End Node', value: isEnd, name: 'isEnd' },
    { label: 'Visit Once', value: isVisitOnce, name: 'isVisitOnce' },
  ];

  return (
    <Container>
      <TextContent>
        <OutlinedInput
          name="title"
          label="Title"
          value={title}
          onChange={handleTitleChange}
          fullWidth
        />
        <NodeContentTitle>Node content</NodeContentTitle>
        <TextEditor
          editorId="text"
          height={300}
          width={800}
          text={text}
          handleEditorChange={handleEditorChange}
          handleKeyDown={handleKeyDown}
        />
      </TextContent>
      <OtherContent>
        {checkBoxes.map((item) => {
          const isChecked = item.value !== ORDINARY_NODE_TYPE && Boolean(item.value);

          return (
            <Switch
              name={item.name}
              key={item.label}
              label={item.label}
              labelPlacement="start"
              checked={isChecked}
              onChange={handleCheckBoxChange}
            />
          );
        })}
      </OtherContent>
    </Container>
  );
};

export default MainTab;
