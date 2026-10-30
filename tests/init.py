# Generated from Selenium IDE
# Test name: init
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait

class Test_init:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_init(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    assert len(self.driver.find_elements(By.XPATH, "//*[@id=\"table-proj_table\"][contains(.,'User Management Wizard Test')]")) == 0
    self.vars["projects"] = self.driver.execute_script("return ['1','2']")
    for self.vars["project"] in self.vars["projects"]:
      self.driver.find_element(By.LINK_TEXT, "New Project").click()
      self.driver.find_element(By.ID, "app_title").send_keys("User Management Wizard Test "+self.vars["project"])
      self.driver.find_element(By.NAME, "purpose").find_element(By.CSS_SELECTOR, "*[value='2']").click()
      None if (element := self.driver.find_element(By.ID, "purpose_other[1]")).is_selected() else element.click()
      self.driver.find_element(By.ID, "project_template_radio1").click()
      self.driver.find_element(By.CSS_SELECTOR, "input[name=\"copyof\"][value=\"1\"]").click()
      self.driver.find_element(By.CSS_SELECTOR, ".btn-primaryrc").click()
      time.sleep(2)
      self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"UserRights/index.php\"]").click()
      self.vars["roles"] = self.driver.execute_script("return ['Admin','Standard1','Standard2']")
      for self.vars["role"] in self.vars["roles"]:
        self.driver.find_element(By.ID, "new_rolename").send_keys(self.vars["role"])
        self.driver.find_element(By.ID, "createRoleBtn").click()
        self.driver.find_element(By.CSS_SELECTOR, "*[aria-describedby=\"editUserPopup\"] .ui-dialog-buttonset button[style*=\"bold\"]").click()
        WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.XPATH, "//table[@id='table-user_rights_roles_table'][contains(.,'"+self.vars["role"]+"')]")))
        time.sleep(0.5)
      self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"route=DataAccessGroupsController:index\"]").click()
      self.vars["dags"] = self.driver.execute_script("return ['DAG1','DAG2']")
      for self.vars["dag"] in self.vars["dags"]:
        self.driver.find_element(By.ID, "new_group").send_keys(self.vars["dag"])
        self.driver.find_element(By.ID, "new_group_button").click()
        WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.XPATH, "//table[@id='table-dags_table'][contains(.,'"+self.vars["dag"]+"')]")))
      self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"ControlCenter/security_settings.php\"]").click()
    self.driver.find_element(By.NAME, "auth_meth_global").find_element(By.CSS_SELECTOR, "*[value='ldap_table']").click()
    self.driver.find_element(By.CSS_SELECTOR, "input[type=\"submit\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"ControlCenter/user_allowlist.php\"]").click()
    self.driver.find_element(By.ID, "enable_user_allowlist").find_element(By.CSS_SELECTOR, "*[value='1']").click()
    self.driver.find_element(By.ID, "enableListBtn").click()
    self.driver.find_element(By.XPATH, "//*[@aria-describedby='allowlistEnableAsk']//button[contains(.,'2)')]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"ExternalModules/manager/control_center.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"user_management_wizard\"] .external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.NAME, "wizard-users")))
    self.driver.find_element(By.NAME, "wizard-users").send_keys("admin", Keys.ENTER, "user1")
    self.driver.find_element(By.NAME, "admin-user").send_keys("admin")
    self.driver.find_element(By.NAME, "internal-user-regex").send_keys("^internal.*")
    self.driver.find_element(By.NAME, "internal-email-regex").send_keys("@internal\\.example\\.com$")
    self.driver.find_element(By.NAME, "default-allowed-roles").send_keys("Standard1")
    self.driver.find_element(By.CSS_SELECTOR, "tr[field=\"project-id\"] .select2").click()
    self.driver.find_element(By.XPATH, "//*[contains(@class,'select2')]//li[contains(.,'User Management Wizard Test 1')]").click()
    self.driver.find_element(By.CSS_SELECTOR, "tr[field=\"project-allowed-roles\"] textarea").send_keys("Standard1", Keys.ENTER, "Standard2")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal button.save").click()
