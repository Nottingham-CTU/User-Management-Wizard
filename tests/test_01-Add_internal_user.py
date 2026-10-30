# Generated from Selenium IDE
# Test name: t01 - Add internal user
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

class Test_01_Add_internal_user:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_01_Add_internal_user(self):
    self.driver.get("http://127.0.0.1/")
    self.vars["username"] = "user1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_switchuser() # Run fn switchuser
    time.sleep(1)
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"prefix=user_management_wizard\"]").click()
    self.driver.find_element(By.ID, "sectionInternal").click()
    self.driver.find_element(By.NAME, "username").send_keys("internal1")
    self.driver.find_element(By.CSS_SELECTOR, "#sectionInternal button").click()
    self.driver.execute_script("$('input[name=\"username\"]').prop('readonly',false)")
    self.driver.find_element(By.NAME, "firstname").send_keys("Internal")
    self.driver.find_element(By.NAME, "lastname").send_keys("User")
    self.driver.find_element(By.NAME, "email").send_keys("1@internal.example.com")
    self.driver.find_element(By.CSS_SELECTOR, "#formContainer button").click()
    self.driver.execute_script("//SETDESC:Assert account created")
    self.driver.find_element(By.XPATH, "//h3[contains(.,'User Project Assignment')]").send_keys("SAVESCREENSHOT")
    assert len(self.driver.find_elements(By.XPATH, "//h3[contains(.,'User Project Assignment')]")) > 0
