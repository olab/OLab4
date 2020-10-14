import styled from 'styled-components';
import {
  DARK_BLUE, BLUE_GREY, WHITE, LIGHT_GREY,
} from '../../shared/colors';

export const ScrollingContainer = styled.div`
  flex: 1 1 100%;
  background-color: ${WHITE};
  overflow: auto;
`;

export const Header = styled.div`
  display: flex;
  justify-content: space-between;
  width: 100%;
  padding: 0 84px;
  background-color: ${LIGHT_GREY};
`;

export const Title = styled.h2`
  margin: 0.7rem;
  font-weight: 800;
  font-style: bold;
  font-size: 24px;
  color: ${DARK_BLUE};
`;

export const TabContainer = styled.div`
  display: flex;
  justify-content: flex-start;
  margin: 20px 100px;
  width: 800px;
  box-sizing: border-box;

  @media only screen and (max-width: 1199px) {
    flex-direction: column;
  }
`;

export const ContainerTab = styled.div`
  display: flex;
  flex-direction: column;
`;

export const Container = styled(ContainerTab)`
  height: 94.5vh;
`;

export const ContentTitle = styled.h3`
  color: ${DARK_BLUE};
  margin: 10px 0;
`;

const styles = () => ({
  paper: {
    margin: '0 auto',
    paddingLeft: 100,
    width: '100%',
    borderRadius: 0,
    boxShadow: 'none',
    borderBottom: `1px solid ${BLUE_GREY}`,
    borderTop: `1px solid ${BLUE_GREY}`,
  },
  button: {
    margin: '0.4rem',
    width: '10rem',
  },
});

export default styles;
