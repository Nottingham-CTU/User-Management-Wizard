# Generated from Selenium IDE
# Test name: t04 - Change roles/DAGs
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

class Test_04_Change_roles_DAGs:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_04_Change_roles_DAGs(self):
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
    self.driver.execute_script("//SETDESC:Assert change role option exists for 'Test 1' project")
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"]").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] .btnShowRoleOptions")) > 0
    self.driver.execute_script("//SETDESC:Assert change role option does not exist for 'Test 2' project")
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 2\"]").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 2\"] .btnShowRoleOptions")) == 0
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] .btnShowRoleOptions").click()
    time.sleep(0.5)
    self.driver.execute_script("//SETDESC:Assert 2 roles in role drop down")
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] [name=\"role_id\"]").send_keys("SAVESCREENSHOT")
    self.vars["numRoles"] = self.driver.execute_script("return '' + $('[data-projectname=\"User Management Wizard Test 1\"] [name=\"role_id\"] option').length")
    assert(self.vars["numRoles"] == "2")
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] [name=\"role_id\"]").find_element(By.XPATH, "(descendant::option)[. = 'Standard2']").click()
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] .btnChgRole").click()
    self.driver.execute_script("//SETDESC:Assert role changed to 'Standard2'")
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"]").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.XPATH, "//*[@data-projectname='User Management Wizard Test 1']//span[contains(text(),'Standard2')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] .btnShowDAGOptions").click()
    None if (element := self.driver.find_element(By.XPATH, "(//*[@data-projectname='User Management Wizard Test 1']//input[contains(@name,'dag[')])[2]")).is_selected() else element.click()
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"] .btnChgDAGs").click()
    self.driver.execute_script("//SETDESC:Assert DAG2 added for 'Test 1' project")
    self.driver.find_element(By.CSS_SELECTOR, "[data-projectname=\"User Management Wizard Test 1\"]").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.XPATH, "//*[@data-projectname='User Management Wizard Test 1']//li[contains(.,'DAG1')]")) > 0
    assert len(self.driver.find_elements(By.XPATH, "//*[@data-projectname='User Management Wizard Test 1']//li[contains(.,'DAG2')]")) > 0
