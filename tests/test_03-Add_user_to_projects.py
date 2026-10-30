# Generated from Selenium IDE
# Test name: t03 - Add user to projects
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait
from fn_switchuser import Test_fn_switchuser as Sub1

class Test_03_Add_user_to_projects:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_03_Add_user_to_projects(self):
    self.driver.get("http://127.0.0.1/")
    self.vars["username"] = "user1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_switchuser() # Run fn switchuser
    time.sleep(1)
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"prefix=user_management_wizard\"]").click()
    self.driver.find_element(By.ID, "sectionExternal").click()
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"firstname\"]").send_keys("REDCap")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"lastname\"]").send_keys("User2")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"email\"]").send_keys("user2@example.com")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal button").click()
    assert len(self.driver.find_elements(By.XPATH, "//h3[contains(.,'User Project Assignment')]")) > 0
    self.driver.execute_script("//SETDESC:Assert no projects in project drop down")
    self.driver.find_element(By.ID, "assign_project_id").send_keys("SAVESCREENSHOT")
    self.vars["numProjs"] = self.driver.execute_script("return '' + $('#assign_project_id option').length")
    assert(self.vars["numProjs"] == "1")
    self.vars["username"] = "admin"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_switchuser() # Run fn switchuser
    time.sleep(1)
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"prefix=user_management_wizard\"]").click()
    self.driver.find_element(By.ID, "sectionExternal").click()
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"firstname\"]").send_keys("REDCap")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"lastname\"]").send_keys("User1")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"email\"]").send_keys("user1@example.com")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal button").click()
    assert len(self.driver.find_elements(By.XPATH, "//h3[contains(.,'User Project Assignment')]")) > 0
    self.driver.find_element(By.ID, "assign_project_id").find_element(By.XPATH, "(descendant::option)[. = 'User Management Wizard Test 1']").click()
    self.driver.find_element(By.ID, "assign_role_id").find_element(By.XPATH, "(descendant::option)[. = 'Admin']").click()
    None if (element := self.driver.find_element(By.NAME, "dag[*]")).is_selected() else element.click()
    self.driver.find_element(By.ID, "btnAssignProj").click()
    self.driver.execute_script("//SETDESC:Assert user added to project")
    self.driver.find_element(By.CSS_SELECTOR, "div[data-projectname=\"User Management Wizard Test 1\"]").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "div[data-projectname=\"User Management Wizard Test 1\"]")) > 0
    self.driver.find_element(By.ID, "assign_project_id").find_element(By.XPATH, "(descendant::option)[. = 'User Management Wizard Test 2']").click()
    self.driver.find_element(By.ID, "assign_role_id").find_element(By.XPATH, "(descendant::option)[. = 'Admin']").click()
    None if (element := self.driver.find_element(By.NAME, "dag[*]")).is_selected() else element.click()
    self.driver.find_element(By.ID, "btnAssignProj").click()
    self.driver.execute_script("//SETDESC:Assert user added to project")
    self.driver.find_element(By.CSS_SELECTOR, "div[data-projectname=\"User Management Wizard Test 2\"]").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "div[data-projectname=\"User Management Wizard Test 2\"]")) > 0
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.vars["username"] = "user1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_switchuser() # Run fn switchuser
    time.sleep(1)
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"prefix=user_management_wizard\"]").click()
    self.driver.find_element(By.ID, "sectionExternal").click()
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"firstname\"]").send_keys("REDCap")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"lastname\"]").send_keys("User2")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal [name=\"email\"]").send_keys("user2@example.com")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionExternal button").click()
    assert len(self.driver.find_elements(By.XPATH, "//h3[contains(.,'User Project Assignment')]")) > 0
    self.driver.execute_script("//SETDESC:Assert 2 projects in project drop down")
    self.driver.find_element(By.ID, "assign_project_id").send_keys("SAVESCREENSHOT")
    self.vars["numProjs"] = self.driver.execute_script("return '' + $('#assign_project_id option').length")
    assert(self.vars["numProjs"] == "3")
    self.driver.find_element(By.ID, "assign_project_id").find_element(By.XPATH, "(descendant::option)[. = 'User Management Wizard Test 1']").click()
    time.sleep(0.5)
    self.driver.execute_script("//SETDESC:Assert 2 roles in role drop down")
    self.driver.find_element(By.ID, "assign_role_id").send_keys("SAVESCREENSHOT")
    self.vars["numRoles"] = self.driver.execute_script("return '' + $('#assign_role_id option').length")
    assert(self.vars["numRoles"] == "3")
    self.driver.find_element(By.ID, "assign_role_id").find_element(By.XPATH, "(descendant::option)[. = 'Standard1']").click()
    None if (element := self.driver.find_element(By.XPATH, "(//*[@id='assign_dags']//input[contains(@name,'dag[')])[1]")).is_selected() else element.click()
    self.driver.find_element(By.ID, "btnAssignProj").click()
    self.driver.execute_script("//SETDESC:Assert user added to project")
    self.driver.find_element(By.CSS_SELECTOR, "div[data-projectname=\"User Management Wizard Test 1\"]").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "div[data-projectname=\"User Management Wizard Test 1\"]")) > 0
    self.driver.find_element(By.ID, "assign_project_id").find_element(By.XPATH, "(descendant::option)[. = 'User Management Wizard Test 2']").click()
    time.sleep(0.5)
    self.driver.execute_script("//SETDESC:Assert 1 role in role drop down")
    self.driver.find_element(By.ID, "assign_role_id").send_keys("SAVESCREENSHOT")
    self.vars["numRoles"] = self.driver.execute_script("return '' + $('#assign_role_id option').length")
    assert(self.vars["numRoles"] == "2")
    self.driver.find_element(By.ID, "assign_role_id").find_element(By.XPATH, "(descendant::option)[. = 'Standard1']").click()
    None if (element := self.driver.find_element(By.XPATH, "(//*[@id='assign_dags']//input[contains(@name,'dag[')])[1]")).is_selected() else element.click()
    self.driver.find_element(By.ID, "btnAssignProj").click()
    self.driver.execute_script("//SETDESC:Assert user added to project")
    self.driver.find_element(By.CSS_SELECTOR, "div[data-projectname=\"User Management Wizard Test 2\"]").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "div[data-projectname=\"User Management Wizard Test 2\"]")) > 0
